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
        $query->setOrderings([
            'sorting' => QueryInterface::ORDER_ASCENDING,
        ]);

        $query->matching(
            $query->equals('page', $pid)
        );

        return $query->execute();
    }
}
