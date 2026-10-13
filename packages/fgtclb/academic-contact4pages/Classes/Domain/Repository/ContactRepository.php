<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Domain\Repository;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Contact>
 */
class ContactRepository extends Repository
{
    private const CONTACT_TABLE = 'tx_academiccontacts4pages_domain_model_contact';

    /**
     * Finds the contacts pointing at the given page through their `page` column, each
     * contact exactly once per language (ACE-430, the read side of ACE-484 on `main`).
     *
     * The row set this method needs is not expressible through Extbase query settings
     * alone: for a translated language it is the records of that language, translations
     * and records that exist only in that language alike, plus the default language
     * records without a translation. That is the union of what `OVERLAYS_MIXED` and
     * `OVERLAYS_ON_WITH_FLOATING` would each select, while neither selects it fully. A
     * pre-query therefore resolves the matching uids per language, one row per contact
     * with a translation preferred over its default record, and the Extbase query fetches
     * exactly those rows with `respectSysLanguage` lifted. The overlay is pinned to
     * `OVERLAYS_MIXED`, which maps a translation onto the identity of its default record
     * and passes an untranslated default record through, the same on TYPO3 v12 and v13.
     * The pinned overlay replaces the overlay type of the site language, so which
     * contacts render does not depend on its fallback type.
     *
     * @return QueryResultInterface<Contact>
     */
    public function findByPid(int $pid, bool $showHidden = false): QueryResultInterface
    {
        $query = $this->createQuery();

        if ($showHidden === true) {
            // Include hidden (disabled) records; other enable fields
            // (deleted, start-/endtime, fe_group) stay in effect.
            $query->getQuerySettings()->setIgnoreEnableFields(true);
            $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        }

        $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
        $contactUids = $this->resolveContactUidsForLanguage(
            $pid,
            $currentLanguageAspect->getContentId(),
            $showHidden,
        );

        $changedLanguageAspect = new LanguageAspect(
            $currentLanguageAspect->getId(),
            $currentLanguageAspect->getContentId(),
            LanguageAspect::OVERLAYS_MIXED,
            $currentLanguageAspect->getFallbackChain()
        );
        $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $query->getQuerySettings()->setRespectStoragePage(false);
        // The contact table is manually sortable (TCA ctrl `sortby`), so the order an editor
        // arranged in the page form is the one the frontend reproduces - Extbase does not read
        // `sortby` by itself. `uid` settles contacts sharing a `sorting` value, as rule 3 of
        // `docs/architecture/database-queries.md` asks: without it the tie belongs to the
        // database, and PostgreSQL, which promises no order without an `ORDER BY`, hands such
        // rows back in the order they were written. The tiebreaker is the uid of the row the
        // query fetches. Under a translated language it is the translation's, which the
        // overlaid object carries as `_localizedUid` while `getUid()` reports its default
        // record, so a tie may sort differently per language, deterministically in each.
        // Neither column is declared in TCA, so the data mapper falls back to the columns of
        // those names.
        $query->setOrderings([
            'sorting' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);

        if ($contactUids === []) {
            // Extbase `in()` rejects an empty value list (exception 1484828466), so an
            // impossible uid keeps the query valid and the result empty.
            $query->matching($query->equals('uid', 0));
        } else {
            $query->matching($query->in('uid', $contactUids));
        }

        return $query->execute();
    }

    /**
     * Resolves which contact rows represent the page's contacts in the given language,
     * exactly one row per contact:
     *
     * - records in language "all" (-1) always match,
     * - in the default language only default language records match,
     * - in a translated language the records of that language match, translations and
     *   records that exist only in that language alike, and a default language record
     *   matches when it has no translation in that language.
     *
     * A default language record whose translation exists is selected through the
     * translation row, so duplicated translations collapse to one row. Should a record
     * have several translations in one language, the lowest uid is selected. Which
     * translation the object is then mapped from is up to the overlay of the core, which
     * looks it up by the default record without an order.
     *
     * The restrictions are what the Extbase frontend query applies to this table:
     * `deleted`, the workspace of the current context and `hidden`, unless $showHidden or
     * the context includes hidden content, as a frontend preview of hidden records does.
     * The contact table declares no other enable column.
     *
     * @return list<int>
     */
    private function resolveContactUidsForLanguage(int $pid, int $languageId, bool $showHidden): array
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $workspaceId = (int)$context->getPropertyFromAspect('workspace', 'id', 0);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::CONTACT_TABLE);
        $restrictions = $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId));
        if (!$showHidden && !$context->getPropertyFromAspect('visibility', 'includeHiddenContent', false)) {
            $restrictions->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }
        $rows = $queryBuilder
            ->select('uid', 'sys_language_uid', 'l10n_parent')
            ->from(self::CONTACT_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'page',
                    $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                ),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $translatedDefaultUids = [];
        if ($languageId > 0) {
            foreach ($rows as $row) {
                if ((int)$row['sys_language_uid'] === $languageId && (int)$row['l10n_parent'] > 0) {
                    // The first translation per default record wins, the rows are ordered by uid.
                    $translatedDefaultUids[(int)$row['l10n_parent']] ??= (int)$row['uid'];
                }
            }
        }

        $uids = [];
        foreach ($rows as $row) {
            $uid = (int)$row['uid'];
            $rowLanguageId = (int)$row['sys_language_uid'];
            $rowTranslationParentUid = (int)$row['l10n_parent'];
            $keep = $rowLanguageId === -1
                || ($rowLanguageId === $languageId
                    && ($rowTranslationParentUid === 0 || ($translatedDefaultUids[$rowTranslationParentUid] ?? 0) === $uid))
                || ($rowLanguageId === 0 && $languageId > 0 && !isset($translatedDefaultUids[$uid]));
            if ($keep) {
                $uids[] = $uid;
            }
        }
        return $uids;
    }
}
