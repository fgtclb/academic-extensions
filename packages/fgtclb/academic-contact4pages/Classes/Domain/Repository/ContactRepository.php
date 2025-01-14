<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Domain\Repository;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    public function findByPid(int $pid): QueryResultInterface
    {
        $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        // With version TYPO3 v12.0 some the method setLanguageOverlayMode() is removed.
        // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-97926-ExtbaseQuerySettingsMethodsRemoved.html
        if (version_compare($versionInformation->getVersion(), '12.0.0', '>=')) {
            $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
            $changedLanguageAspect = new LanguageAspect(
                $currentLanguageAspect->getId(),
                $currentLanguageAspect->getContentId(),
                LanguageAspect::OVERLAYS_ON,
                $currentLanguageAspect->getFallbackChain()
            );
            $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        } else {
            $query->getQuerySettings()->setLanguageOverlayMode(true);
        }

        $query->matching(
            $query->equals('page', $pid)
        );

        return $query->execute();
    }
}
