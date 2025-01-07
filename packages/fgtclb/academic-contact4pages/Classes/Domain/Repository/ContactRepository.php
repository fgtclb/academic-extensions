<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Domain\Repository;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ContactRepository extends Repository
{
    /**
     * @return QueryResultInterface<Contact>
     */
    public function findByPid(int $pid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setLanguageOverlayMode(true);
        $query->matching(
            $query->equals('page', $pid)
        );

        return $query->execute();
    }
}
