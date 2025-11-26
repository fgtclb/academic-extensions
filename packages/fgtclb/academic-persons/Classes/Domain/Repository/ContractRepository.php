<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Repository;

use Fgtclb\AcademicPersons\Domain\Model\Contract;
use Fgtclb\AcademicPersons\Domain\Model\Dto\ContractDemand;
use Fgtclb\AcademicPersons\Domain\Model\Dto\ContractDemandInterface;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Contract>
 */
class ContractRepository extends Repository
{
    public function findByDemand(ContractDemandInterface $demand): QueryResultInterface
    {
        $query = $this->createQuery();
        $this->applyDemandSettings($query, $demand);
        $this->applyDemandForQuery($query, $demand);
        return $query->execute();
    }

    private function applyDemandSettings(QueryInterface $query, ContractDemandInterface $demand)
    {
        // Selected uid's are default language and we need to configure extbase in away to
        // properly handle the overlay. This is adopted from the generic extbase backend
        // implementation.
        if (method_exists($query->getQuerySettings(), 'getLanguageAspect')
            && method_exists($query->getQuerySettings(), 'setLanguageAspect')
        ) {
            $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
            $changedLanguageAspect = new LanguageAspect(
                $currentLanguageAspect->getId(),
                $currentLanguageAspect->getContentId(),
                $currentLanguageAspect->getOverlayType() === LanguageAspect::OVERLAYS_OFF ? LanguageAspect::OVERLAYS_ON_WITH_FLOATING : $currentLanguageAspect->getOverlayType()
            );
            $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        } else {
            // @todo Remove this when TYPO3 v11 support is dropped with 2.x.x.
            if (method_exists($query->getQuerySettings(), 'setLanguageOverlayMode')) {
                $query->getQuerySettings()->setLanguageOverlayMode(true);
            }
        }

        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
    }

    private function applyDemandForQuery(QueryInterface $query, ContractDemandInterface $demand): void
    {
        $filters = [];

        if ($demand->getContractList() !== []) {
            $filters[] = $query->in('uid', $demand->getContractList());
        }

        if ($demand->getShowPublicOnly() === true) {
            $filters[] = $query->equals('profile.allowedShowPublic', 1);
        }

        $query->matching($query->logicalAnd(...$filters));
    }
}
