<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Repository;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Domain\Model\Project;
use FGTCLB\AcademicProjects\Enumeration\PageTypes;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
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

        $constraints = [];
        $constraints[] = $query->equals('doktype', PageTypes::TYPE_ACEDEMIC_PROJECT);

        if (!empty($demand->getPages())) {
            if ($demand->getShowSelected() === true) {
                $constraints[] = $query->in('uid', $demand->getPages());
                // Selecting record ids in the backend (FormEngine) are always persisted using the default language
                // uid of the records unrelated to the language and leads to missing translated contend depending on
                // the site configuration and frontend context. In these cases we need to disable respecting the
                // system langauge to tell Extbase ORM to do proper overlay handling in this case.
                $query->getQuerySettings()->setRespectSysLanguage(false);
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
}
