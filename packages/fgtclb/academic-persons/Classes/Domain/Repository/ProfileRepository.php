<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Repository;

use FGTCLB\AcademicPersons\DemandValues\GroupByValues;
use FGTCLB\AcademicPersons\DemandValues\SortByValues;
use FGTCLB\AcademicPersons\Domain\Model\Dto\DemandInterface;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Profile>
 */
class ProfileRepository extends Repository
{
    protected EventDispatcherInterface $eventDispatcher;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return QueryResultInterface<int, Profile>
     */
    public function findByDemand(DemandInterface $demand): QueryResultInterface
    {
        $query = $this->createQuery();
        $demand = $this->eventDispatcher->dispatch(new ModifyProfileDemandEvent($demand))->getDemand();
        $this->applyDemandSettings($query, $demand);
        $this->applyDemandForQuery($query, $demand);
        return $query->execute();
    }

    /**
     * @param QueryInterface<Profile> $query
     */
    private function applyDemandSettings(QueryInterface $query, DemandInterface $demand): void
    {
        // @todo Remove method_exists() level (unnesting block) with next major, when added breaking to DemandInterface
        //       and deprecation layer in ProfileController::adoptSettings().
        if (method_exists($demand, 'getStoragePages')) {
            if ($demand->getStoragePages() !== '') {
                $query->getQuerySettings()->setStoragePageIds(
                    GeneralUtility::intExplode(',', $demand->getStoragePages(), true)
                );
            } else {
                $query->getQuerySettings()->setRespectStoragePage(false);
            }
        } else {
            trigger_error(
                sprintf(
                    'Class "%s" does not implement methods "%s" and "%s", which is deprecated, and will be added '
                    . 'breaking with 1.x to interface "%s". Interface already includes commented method signature.',
                    $demand::class,
                    'setStoragePages',
                    'getStoragePages',
                    DemandInterface::class,
                ),
                E_USER_DEPRECATED
            );
        }

        /**
         * Introduced with https://github.com/fgtclb/academic-persons/pull/30 to have the option to display profiles in
         * fallback mode even when site language (non-default) is configured to be in strict mode.
         *
         * {@see AcademicPersonsListAndDetailPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
         * {@see AcademicPersonsListPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
         *
         * @todo    Remove `method_exists()' check with next major, when added breaking to DemandInterface and deprecation
         *          layer in ProfileController::adoptSettings().
         */
        if (method_exists($demand, 'getFallbackForNonTranslated')) {
            if ($demand->getFallbackForNonTranslated() === 1) {
                $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
                // @see self::findByUids().
                $changedLanguageAspect = new LanguageAspect(
                    $currentLanguageAspect->getId(),
                    $currentLanguageAspect->getContentId(),
                    LanguageAspect::OVERLAYS_MIXED
                );
                $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
            }
        } else {
            trigger_error(
                sprintf(
                    'Class "%s" does not implement methods "%s" and "%s", which is deprecated, and will be added '
                    . 'breaking with 1.x to interface "%s". Interface already includes commented method signature.',
                    $demand::class,
                    'setFallbackForNonTranslated',
                    'getFallbackForNonTranslated',
                    DemandInterface::class,
                ),
                E_USER_DEPRECATED
            );
        }
    }

    /**
     * @param QueryInterface<Profile> $query
     */
    private function applyDemandForQuery(QueryInterface $query, DemandInterface $demand): void
    {
        // Direct selected profiles make all other filters and orderings obsolete and is handled first.
        if ($demand->getProfileList() !== '') {
            $profileUidArray = GeneralUtility::intExplode(',', $demand->getProfileList(), true);
            $query->getQuerySettings()->setRespectSysLanguage(false);
            $query->matching($query->in('uid', $profileUidArray));
            return;
        }

        $filters = $this->setFilters($query, $demand);
        if ($filters !== null) {
            $query->matching($filters);
        }
        $query->setOrderings($this->getOrderingsFromDemand($demand));
    }

    /**
     * @param QueryInterface<Profile> $query
     */
    private function setFilters(QueryInterface $query, DemandInterface $demand): ?ConstraintInterface
    {
        $filters = [];

        if (method_exists($demand, 'getFunctionTypes')
            && $demand->getFunctionTypes() !== []) {
            $filters[] = $query->in('contracts.functionType', $demand->getFunctionTypes());
        }

        if (method_exists($demand, 'getOrganisationalUnits')
            && $demand->getOrganisationalUnits() !== []) {
            $filters[] = $query->in('contracts.organisationalUnit', $demand->getOrganisationalUnits());
        }

        if ($demand->getAlphabetFilter() != '') {
            $filters[] = $query->like('last_name', $demand->getAlphabetFilter() . '%');
        }

        return ($filters === [])
            ? null
            : $query->logicalAnd(...$filters);
    }

    /**
     * @return array<string, string>
     */
    private function getOrderingsFromDemand(DemandInterface $demand): array
    {
        $orderings = [];
        $allowedGroupingValues = array_keys(GeneralUtility::makeInstance(GroupByValues::class)->getAll());
        $allowedSortByValues = array_keys(GeneralUtility::makeInstance(SortByValues::class)->getAll());
        $allowedSortByDirectionValues = ['asc', 'desc'];
        if (in_array($demand->getGroupBy(), $allowedGroupingValues, true)) {
            $orderings[$demand->getGroupBy()] = QueryInterface::ORDER_ASCENDING;
        }
        if (in_array($demand->getSortBy(), $allowedSortByValues, true)
            && in_array($demand->getSortByDirection(), $allowedSortByDirectionValues, true)
        ) {
            $orderings[$demand->getSortBy()] = strtoupper($demand->getSortByDirection());
        }
        return $orderings;
    }

    /**
     * @param int[] $uids
     * @return QueryResultInterface<int, Profile>
     */
    public function findByUids(array $uids): QueryResultInterface
    {
        $query = $this->createQuery();
        // Selected uid's are default language and we need to configure extbase in away to
        // properly handle the overlay. This is adopted from the generic extbase backend
        // implementation.
        $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
        $changedLanguageAspect = new LanguageAspect(
            $currentLanguageAspect->getId(),
            $currentLanguageAspect->getContentId(),
            $currentLanguageAspect->getOverlayType() === LanguageAspect::OVERLAYS_OFF ? LanguageAspect::OVERLAYS_ON_WITH_FLOATING : $currentLanguageAspect->getOverlayType()
        );
        $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $query->matching($query->in('uid', $uids));
        return $query->execute();
    }

    /**
     * @param int $frontendUserUid
     * @return QueryResultInterface<int, Profile>
     */
    public function findByFrontendUser(int $frontendUserUid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query
            ->matching(
                $query->contains('frontendUsers', $frontendUserUid)
            )
            ->execute();
    }
}
