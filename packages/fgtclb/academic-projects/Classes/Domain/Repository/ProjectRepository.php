<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Repository;

use FGTCLB\AcademicProjects\Domain\Enumeration\Page;
use FGTCLB\AcademicProjects\Domain\Enumeration\SortingOptions;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectFilter;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ProjectRepository extends Repository
{
    /**
     * @param int[] $pages
     * @throws InvalidEnumerationValueException
     */
    public function findByFilter(
        bool $hideCompletedProjects,
        ?ProjectFilter $filter = null,
        array $pages = [],
        bool $selected = false
    ): QueryResult {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->equals('doktype', Page::TYPE_ACEDEMIC_PROJECT);

        if ($filter) {
            if (!empty($filter->getFilterCategories())) {
                foreach ($filter->getFilterCategories() as $category) {
                    if (is_numeric($category) && (int)$category) {
                        $constraints[] = $query->contains('categories', $category);
                    }
                }
            }
        }
        if ($selected && !empty($pages)) {
            $constraints[] = $query->in('uid', $pages);
        }

        if ($hideCompletedProjects) {
            $constraints[] = $query->logicalOr(
                $query->greaterThanOrEqual('tx_academicprojects_end_date', new \DateTime()),
                $query->equals('tx_academicprojects_end_date', 0)
            );
        } else {
            $query->getQuerySettings()->setIgnoreEnableFields(true);
        }

        if (!empty($constraints)) {
            $query->matching(
                $query->logicalAnd($constraints)
            );
        }

        [$sortingField, $sortingDirection] = explode(' ', SortingOptions::__default);
        if ($filter && $filter->getSorting()) {
            if (in_array($filter->getSorting(), SortingOptions::getConstants())) {
                [$sortingField, $sortingDirection] = explode(' ', $filter->getSorting());
            }
        }

        $query->setOrderings(
            [
                $sortingField => strtoupper($sortingDirection),
            ]
        );

        return $query->execute();
    }
}
