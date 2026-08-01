<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Repository;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Domain\Model\Project;
use FGTCLB\AcademicProjects\Enumeration\PageTypes;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Project>
 */
class ProjectRepository extends Repository
{
    /**
     * @return QueryResult<Project>
     * @throws InvalidEnumerationValueException
     */
    public function findByDemand(ProjectDemand $demand): QueryResult
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        if ($demand->getShowHiddenRecords() === true) {
            // Include hidden (disabled) records; other enable fields
            // (deleted, start-/endtime, fe_group) stay in effect.
            $query->getQuerySettings()->setIgnoreEnableFields(true);
            $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        }

        $constraints = [];
        $constraints[] = $query->equals('doktype', PageTypes::TYPE_ACEDEMIC_PROJECT);

        if (!empty($demand->getPages())) {
            if ($demand->getShowSelected() === true) {
                $constraints[] = $query->in('uid', $demand->getPages());
                $this->matchSelectedUidsAcrossLanguages($query);
            } else {
                $constraints[] = $query->in('pid', $demand->getPages());
                $query->getQuerySettings()->setRespectStoragePage(true);
            }
        }

        if ($demand->getFilterCollection() !== null) {
            foreach ($demand->getFilterCollection()->getFilterCategories() as $category) {
                $constraints[] = $query->contains('categories', $category->getUid());
            }
        }

        $activeState = ActiveState::tryFromDefault($demand->getActiveState());
        if ($activeState === ActiveState::ACTIVE) {
            $constraints[] = $query->logicalOr(
                ...array_values(
                    [
                        $query->equals('txAcademicprojectsEndDate', 0),
                        $query->greaterThan('txAcademicprojectsEndDate', new \DateTime()),
                    ]
                )
            );
        }

        if ($activeState === ActiveState::COMPLETED) {
            $constraints[] = $query->logicalAnd(
                ...array_values(
                    [
                        $query->greaterThan('txAcademicprojectsEndDate', 0),
                        $query->lessThan('txAcademicprojectsEndDate', new \DateTime()),
                    ]
                )
            );
        }

        $query->matching(
            $query->logicalAnd(...array_values($constraints))
        );

        $query->setOrderings(
            [
                $demand->getSortingField() => strtoupper($demand->getSortingDirection()),
            ]
        );

        return $query->execute();
    }

    /**
     * Prepare a query that matches records by uid taken from a manual selection.
     *
     * FormEngine persists such a selection as **default language** uids, so the language
     * restriction has to come off - otherwise nothing matches in a translation. That
     * alone is not enough: with `fallbackType: free` the aspect carries `OVERLAYS_OFF`,
     * and the default language rows are then handed to the frontend unoverlaid, which is
     * how a German page ended up listing English projects (ACE-341). So the aspect is
     * lifted to `OVERLAYS_ON_WITH_FLOATING` first, and only then is the restriction
     * dropped.
     *
     * Adopted from the generic Extbase backend implementation, and the same shape as
     * `EXT:academic_persons` `ProfileRepository::matchSelectedUidsAcrossLanguages()`.
     *
     * @param QueryInterface<Project> $query
     */
    private function matchSelectedUidsAcrossLanguages(QueryInterface $query): void
    {
        $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
        $changedLanguageAspect = new LanguageAspect(
            $currentLanguageAspect->getId(),
            $currentLanguageAspect->getContentId(),
            $currentLanguageAspect->getOverlayType() === LanguageAspect::OVERLAYS_OFF ? LanguageAspect::OVERLAYS_ON_WITH_FLOATING : $currentLanguageAspect->getOverlayType()
        );
        $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        $query->getQuerySettings()->setRespectSysLanguage(false);
    }
}
