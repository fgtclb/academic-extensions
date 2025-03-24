<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Repository;

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

        if ($demand->getHideCompletedProjects() === true) {
            $constraints[] = $query->logicalOr(
                ...array_values(
                    [
                        $query->equals('txAcademicprojectsEndDate', 0),
                        $query->greaterThan('txAcademicprojectsEndDate', new \DateTime()),
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
