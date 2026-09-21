<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Domain\Repository;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Contact>
 */
class ContactRepository extends Repository
{
    /**
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
        $changedLanguageAspect = new LanguageAspect(
            $currentLanguageAspect->getId(),
            $currentLanguageAspect->getContentId(),
            LanguageAspect::OVERLAYS_ON,
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
        // rows back in the order they were written. The query carries no language constraint
        // at all, so it fetches the same rows in every language - a translation next to its
        // default record, the defect `ContactRepositoryFindByPidTest` pins - and orders them
        // by the raw `uid` column, which resolves a tie identically in every language context.
        // That column is not what the overlaid object reports: a default row overlaid by its
        // translation carries the translation's uid as `_localizedUid`. Neither `sorting` nor
        // `uid` is declared in TCA, so the data mapper falls back to the columns of those
        // names.
        $query->setOrderings([
            'sorting' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);

        $query->matching(
            $query->equals('page', $pid)
        );

        return $query->execute();
    }
}
