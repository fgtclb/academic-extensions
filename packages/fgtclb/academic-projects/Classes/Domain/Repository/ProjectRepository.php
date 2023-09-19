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
        ?ProjectFilter $filter = null,
        array $pages = []
    ): QueryResult {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->equals('doktype', Page::TYPE_EDUCATIONAL_PROJECT);

        if ($filter) {
            $categories = [];
            if (!empty($filter->getFilterCategories())) {
                foreach ($filter->getFilterCategories() as $category) {
                    if (is_numeric($category) && (int)$category) {
                        $categories[] = $category;
                    }
                }
            }
            if (!empty($categories)) {
                $constraints[] = $query->contains('categories', $categories);
            }
        }

        $query->matching(
            $query->logicalAnd($constraints)
        );

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
