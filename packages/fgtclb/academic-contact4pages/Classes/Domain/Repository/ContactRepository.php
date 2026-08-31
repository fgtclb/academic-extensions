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
     * Finds the contacts pointing at the given page through their `page` column,
     * each contact exactly once per language context (ACE-484).
     *
     * The language handling works in two steps, because the row set this method
     * needs is not expressible through Extbase query settings alone: for a
     * translated language it is "records of that language - connected translations
     * and language-only records alike - plus the default-language records without a
     * translation", which is the union of what `OVERLAYS_MIXED` and
     * `OVERLAYS_ON_WITH_FLOATING` would each select while neither selects it fully.
     * A raw pre-query therefore resolves the matching uids per language (one row
     * per contact, translations preferred over their default record), and the
     * Extbase query fetches exactly those rows with `respectSysLanguage` lifted.
     * The overlay type is pinned to `OVERLAYS_MIXED`, which maps a translated row
     * onto its default record's identity (`_localizedUid`) and passes untranslated
     * default records through - identical on TYPO3 v13 and v14, and independent of
     * the site's own fallback configuration, which must not reintroduce duplicates
     * or drop rows the pre-query selected.
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
        $query->setOrderings([
            'sorting' => QueryInterface::ORDER_ASCENDING,
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
     * Resolves which contact rows represent the page's contacts in the given
     * language, exactly one row per contact:
     *
     * - records in language "all" (-1) always match,
     * - in the default language only default-language records match,
     * - in a translated language the records of that language match - connected
     *   translations as well as records existing only in that language - and a
     *   default-language record matches when it has no translation in that language.
     *
     * A default-language record whose translation exists is represented by the
     * translation row, so legacy duplicated translations (created by the former
     * synchronizer recursion, ACE-103) collapse to one row; should a record carry
     * several translations in one language, the lowest uid wins deterministically.
     *
     * The restrictions mirror what the Extbase query applies: `deleted` always,
     * `hidden` unless $showHidden, and the workspace of the current context - the
     * contact table declares no other enable columns.
     *
     * @return list<int>
     */
    private function resolveContactUidsForLanguage(int $pid, int $languageId, bool $showHidden): array
    {
        $workspaceId = (int)GeneralUtility::makeInstance(Context::class)
            ->getPropertyFromAspect('workspace', 'id', 0);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::CONTACT_TABLE);
        $restrictions = $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId));
        if (!$showHidden) {
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
                    // First translation per default record wins (rows are ordered by uid).
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
