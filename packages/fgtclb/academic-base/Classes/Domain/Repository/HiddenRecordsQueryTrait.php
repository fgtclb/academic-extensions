<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Queries of an Extbase repository that include hidden records, for the plugin option
 * "Show hidden records", in the default language and in a translation alike (ACE-826).
 *
 * Ignoring the hidden flag in the query settings reaches the query itself only. Two
 * steps of a translated query do not read those settings:
 *
 * - Extbase overlays the translation through `PageRepository`, which follows the
 *   visibility aspect of the context. The hidden translation of a hidden record was not
 *   found, and the record was rendered in the default language.
 * - On TYPO3 v12 the language statement selects the translations of the default records
 *   with a subquery that excludes hidden default records unconditionally. The hidden
 *   record was missing from a translated list. TYPO3 v13 applies the query settings to
 *   that subquery.
 *
 * A repository prepares the query with {@see self::includeHiddenRecords()}, completes
 * its constraints, and executes it between {@see self::matchTranslationsOfHiddenRecords()}
 * and {@see self::fetchIncludingHiddenRecords()}. Both of them leave a query that does
 * not include hidden records alone, so they can be called unconditionally.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
trait HiddenRecordsQueryTrait
{
    /**
     * Include hidden records in the query. Only the hidden flag is ignored, deleted
     * records, start and end time and frontend user groups keep deciding.
     *
     * @template T of object
     * @param QueryInterface<T> $query
     */
    private function includeHiddenRecords(QueryInterface $query): void
    {
        $querySettings = $query->getQuerySettings();
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setEnableFieldsToBeIgnored(['disabled']);
    }

    /**
     * Replace the language statement of TYPO3 v12 for a query that includes hidden
     * records, in a language with overlays that leave untranslated records out
     * (`fallbackType: strict`). Call it once the constraints of the query are complete.
     *
     * The statement of the core selects the translations whose default record a subquery
     * finds, and that subquery excludes hidden default records whatever the query
     * settings say. Here the translations of the language are matched directly instead,
     * and the language restriction of the query settings is turned off. The overlay still
     * reads the default record of each translation, and drops a translation whose default
     * record is deleted. A translation whose default record is outside its start and end
     * time is left out by uid, see {@see self::findDefaultRecordsOutsideTheirTimeWindow()}.
     * The frontend user groups of the default record are not checked, a page translation
     * carries those of its default page.
     *
     * The mixed mode of `fallbackType: fallback` is not replaced. Its statement on TYPO3
     * v12 also finds the translations through subqueries that exclude hidden records, so
     * where a default record and its translation differ in visibility, the record is listed
     * twice (default visible, translation hidden) or not at all (default hidden,
     * translation visible). Replacing it would need the uids of every translated record of
     * the table as a parameter list, which is not safe for the `pages` table. A query that
     * does not restrict the language itself needs no replacement, and neither does any
     * query on TYPO3 v13.
     *
     * @todo Remove together with the support of TYPO3 v12.
     * @template T of object
     * @param QueryInterface<T> $query
     */
    private function matchTranslationsOfHiddenRecords(QueryInterface $query): void
    {
        $querySettings = $query->getQuerySettings();
        if ((new Typo3Version())->getMajorVersion() >= 13
            || !$this->queryIncludesHiddenRecords($query)
            || !$querySettings->getRespectSysLanguage()
        ) {
            return;
        }
        $tableName = $this->getQueriedTableName($query);
        $languageAspect = $querySettings->getLanguageAspect();
        $languageField = (string)($GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? '');
        $transOrigPointerField = (string)($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] ?? '');
        if ($languageField === ''
            || $transOrigPointerField === ''
            || $languageAspect->getContentId() <= 0
            || !in_array(
                $languageAspect->getOverlayType(),
                [LanguageAspect::OVERLAYS_ON, LanguageAspect::OVERLAYS_ON_WITH_FLOATING],
                true
            )
        ) {
            return;
        }

        $languageProperty = GeneralUtility::underscoredToLowerCamelCase($languageField);
        $transOrigPointerProperty = GeneralUtility::underscoredToLowerCamelCase($transOrigPointerField);
        $translations = $query->equals($languageProperty, $languageAspect->getContentId());
        if ($languageAspect->getOverlayType() === LanguageAspect::OVERLAYS_ON) {
            // Floating translations, those without a default record, are left out in this mode.
            $translations = $query->logicalAnd(
                $translations,
                $query->greaterThan($transOrigPointerProperty, 0),
            );
        }
        $defaultRecordsOutsideTheirTimeWindow = $this->findDefaultRecordsOutsideTheirTimeWindow(
            $tableName,
            $languageField,
            $transOrigPointerField,
            $languageAspect->getContentId(),
        );
        // Extbase rejects an empty list for `in()` on TYPO3 v12, so the constraint is only
        // added when there is something to leave out.
        if ($defaultRecordsOutsideTheirTimeWindow !== []) {
            $translations = $query->logicalAnd(
                $translations,
                $query->logicalNot($query->in($transOrigPointerProperty, $defaultRecordsOutsideTheirTimeWindow)),
            );
        }
        $languageConstraint = $query->logicalOr(
            $query->equals($languageProperty, -1),
            $translations,
        );
        $constraint = $query->getConstraint();
        $query->matching(
            $constraint === null ? $languageConstraint : $query->logicalAnd($constraint, $languageConstraint)
        );
        $querySettings->setRespectSysLanguage(false);
    }

    /**
     * The uids of the default records of a table that are outside their start and end
     * time and have a translation in the language, the part of the visibility the
     * subquery of the core checks and the translation does not inherit. Only translated
     * records are read, so a query on `pages` does not bind every expired page of the
     * installation. Deleted records are left out, they cannot be the default record of a
     * listed translation anyway.
     *
     * A preview of scheduled records lifts the check. That matches the main query and the
     * subquery of TYPO3 v13, while the subquery of TYPO3 v12 checks the time regardless.
     *
     * @return list<int>
     */
    private function findDefaultRecordsOutsideTheirTimeWindow(
        string $tableName,
        string $languageField,
        string $transOrigPointerField,
        int $languageId,
    ): array {
        $enableColumns = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'] ?? [];
        $startTimeField = (string)($enableColumns['starttime'] ?? '');
        $endTimeField = (string)($enableColumns['endtime'] ?? '');
        $context = GeneralUtility::makeInstance(Context::class);
        if (($startTimeField === '' && $endTimeField === '')
            || (bool)$context->getPropertyFromAspect('visibility', 'includeScheduledRecords', false)
        ) {
            return [];
        }
        $accessTime = (int)$context->getPropertyFromAspect('date', 'accessTime', 0);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $outsideTimeWindow = [];
        if ($startTimeField !== '') {
            $outsideTimeWindow[] = $queryBuilder->expr()->gt(
                $startTimeField,
                $queryBuilder->createNamedParameter($accessTime, Connection::PARAM_INT)
            );
        }
        if ($endTimeField !== '') {
            $outsideTimeWindow[] = $queryBuilder->expr()->and(
                $queryBuilder->expr()->gt($endTimeField, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->lte(
                    $endTimeField,
                    $queryBuilder->createNamedParameter($accessTime, Connection::PARAM_INT)
                ),
            );
        }
        // The parameters of the subquery are bound on the builder that executes it.
        $translations = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $translations->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $translations
            ->select('translation.' . $transOrigPointerField)
            ->from($tableName, 'translation')
            ->where(
                $translations->expr()->eq(
                    'translation.' . $languageField,
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                ),
                $translations->expr()->gt(
                    'translation.' . $transOrigPointerField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
            );
        $uids = $queryBuilder
            ->select('uid')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq($languageField, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->or(...$outsideTimeWindow),
                $queryBuilder->expr()->in('uid', $translations->getSQL()),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $uids);
    }

    /**
     * Fetch the result of a query that includes hidden records while the visibility
     * aspect of the context includes hidden records of the queried table as well, and
     * restore the aspect afterwards. Iterating the result later reuses the fetched
     * objects.
     *
     * Relations that load eagerly are fetched within the same window, for every record of
     * the result, visible ones included. Their hidden records of the same kind are
     * included as well: hidden pages for a query on `pages`, hidden content records, image
     * references among them, for any other table.
     *
     * @template T of object
     * @param QueryResultInterface<int, T> $result
     */
    private function fetchIncludingHiddenRecords(QueryResultInterface $result): void
    {
        $query = $result->getQuery();
        if (!$this->queryIncludesHiddenRecords($query)) {
            return;
        }
        $tableName = $this->getQueriedTableName($query);
        $context = GeneralUtility::makeInstance(Context::class);
        $visibilityAspect = $context->getAspect('visibility');
        $context->setAspect('visibility', new VisibilityAspect(
            includeHiddenPages: $tableName === 'pages' || (bool)$visibilityAspect->get('includeHiddenPages'),
            includeHiddenContent: $tableName !== 'pages' || (bool)$visibilityAspect->get('includeHiddenContent'),
            includeDeletedRecords: (bool)$visibilityAspect->get('includeDeletedRecords'),
            includeScheduledRecords: (bool)$visibilityAspect->get('includeScheduledRecords'),
        ));
        try {
            $result->toArray();
        } finally {
            $context->setAspect('visibility', $visibilityAspect);
        }
    }

    /**
     * @template T of object
     * @param QueryInterface<T> $query
     */
    private function queryIncludesHiddenRecords(QueryInterface $query): bool
    {
        $querySettings = $query->getQuerySettings();
        return $querySettings->getIgnoreEnableFields()
            && in_array('disabled', $querySettings->getEnableFieldsToBeIgnored(), true);
    }

    /**
     * @template T of object
     * @param QueryInterface<T> $query
     */
    private function getQueriedTableName(QueryInterface $query): string
    {
        $source = $query->getSource();
        if (!$source instanceof SelectorInterface) {
            throw new \LogicException(
                sprintf('Hidden records can only be included in a query on a single table, "%s" given.', get_class($source)),
                1791459866
            );
        }
        return $source->getSelectorName();
    }
}
