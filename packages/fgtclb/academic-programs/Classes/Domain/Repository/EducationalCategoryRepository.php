<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Repository;

use FGTCLB\EducationalCourse\Database\Query\Restriction\LanguageRestriction;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EducationalCategoryRepository
{
    private ConnectionPool $connection;

    public function __construct()
    {
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function findChildren(int $uid): ?CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $statement = $queryBuilder
            ->select('uid', 'title', 'type')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'parent',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in('type', array_map(function (string $value) {
                    return '\'' . $value . '\'';
                }, array_values(Category::getConstants())))
            );

        $children = new CategoryCollection();

        $result = $statement->executeQuery()->fetchAllAssociative();

        foreach ($result as $child) {
            $children->attach(
                new EducationalCategory(
                    $child['uid'],
                    Category::cast($child['type']),
                    $child['title']
                )
            );
        }
        return $children;
    }

    private function buildQueryBuilder(string $tableName = 'sys_category'): QueryBuilder
    {
        $queryBuilder = $this->connection->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(LanguageRestriction::class));

        return $queryBuilder;
    }
}
