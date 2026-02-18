<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use Doctrine\DBAL\Result;
use FGTCLB\AcademicBase\Tca\TableConfiguration;
use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal being experimental for now until implementation has been streamlined, tested and covered with tests.
 * @final not marked as final for functional testing reasons (for now). Class should not be extended otherwise.
 */
#[AsAlias(id: RecordSynchronizerInterface::class, public: true)]
#[Autoconfigure(public: true)]
class RecordSynchronizer implements RecordSynchronizerInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function synchronize(SynchronizerContext $context): void
    {
        $this->synchronizeRecord($context, []);
    }

    /**
     * @param array<string, int|float|string|bool|null> $values
     * @throws \Doctrine\DBAL\Exception
     */
    private function synchronizeRecord(SynchronizerContext $context, array $values): void
    {
        $defaultRecord = $this->getDefaultRecord(
            $context->tableName,
            $context->uid,
            $context->defaultLanguage->getLanguageId(),
        );
        if ($defaultRecord === null) {
            return;
        }
        foreach ($context->allowedSiteLanguages as $allowedSiteLanguage) {
            $translatedRecord = $this->getTranslatedRecord(
                $context->tableName,
                $context->uid,
                $allowedSiteLanguage->getLanguageId(),
            );
            if ($translatedRecord !== null) {
                $this->updateTranslation(
                    $context,
                    $defaultRecord,
                    $translatedRecord,
                );
                continue;
            }
            $translatedRecord = $this->createTranslation(
                $context,
                $defaultRecord,
                $allowedSiteLanguage->getLanguageId(),
                $values,
            );
            if ($translatedRecord === null) {
                // Failed to create translation record, skip relation synchronization.
                continue;
            }
            foreach ($context->tableConfiguration->columns() as $columnName => $columnDefinition) {
                $columnType = $columnDefinition['type'] ?? 'unknown';

                // @todo Column name `sys_file_reference` exclude does not make sense and should be most likely
                //       `foreign_table` and will investigated at a later point, kept for now during moving code
                //       around to prepare for better testability and avoiding a side task for now.
                if ($columnType === 'inline' && $columnName !== 'sys_file_reference') {
                    $this->synchronizeInlineField(
                        $context,
                        $defaultRecord,
                        $translatedRecord,
                        $columnName,
                        $columnDefinition,
                    );
                    continue;
                }

                // @todo Handle other relation types ?!
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultRecord(
        string $table,
        int $uid,
        int $defaultLanguageId,
    ): ?array {
        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

        $queryBuilder = $this->getQueryBuilder($table);
        $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $tcaCtrl['languageField'],
                    $queryBuilder->createNamedParameter($defaultLanguageId, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        $resultArray = $queryBuilder->executeQuery()->fetchAssociative();

        return $resultArray ?: null;
    }

    /**
     * @param string $table
     * @param int $uid
     * @param int $languageUid
     * @return array<string, mixed>
     */
    private function getTranslatedRecord(
        string $table,
        int $uid,
        int $languageUid
    ): ?array {
        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

        $queryBuilder = $this->getQueryBuilder($table);
        $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $tcaCtrl['translationSource'] ?? $tcaCtrl['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $tcaCtrl['languageField'],
                    $queryBuilder->createNamedParameter((int)$languageUid, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        $resultArray = $queryBuilder->executeQuery()->fetchAssociative();

        return $resultArray ?: null;
    }

    /**
     * @param array<string, mixed> $defaultRecord
     * @param array<string, mixed> $values
     * @return array<string, mixed>|null
     */
    private function createTranslation(
        SynchronizerContext $context,
        array $defaultRecord,
        int $languageId,
        array $values = []
    ): ?array {
        $values = $this->getValuesForNewRecordTranslation(
            $context,
            $defaultRecord,
            $languageId,
            $values,
        );
        $this->getQueryBuilder($context->tableName)
            ->insert($context->tableName)
            ->values($values)
            ->executeStatement();
        return $this->getTranslatedRecord(
            $context->tableName,
            $defaultRecord['uid'],
            $languageId,
        );
    }

    /**
     * @param array<string, mixed> $defaultRecord
     * @param array<string, mixed> $translatedRecord
     */
    private function updateTranslation(
        SynchronizerContext $context,
        array $defaultRecord,
        array $translatedRecord,
    ): void {
        $updateColumns = $this->getColumnNamedForTranslatedRecordUpdate($context);
        if ($updateColumns === null) {
            // Skip if there are no columns to update
            return;
        }
        $queryBuilder = $this->getQueryBuilder($context->tableName);
        $queryBuilder->update($context->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($translatedRecord['uid'], Connection::PARAM_INT)
                ),
            );
        foreach ($updateColumns as $columnName) {
            $queryBuilder->set($columnName, $defaultRecord[$columnName]);
        }
        $queryBuilder->executeStatement();
    }

    /**
     * @param array<string, mixed> $defaultRecord
     * @param array<string, mixed> $translatedRecord
     * @param array<string, mixed> $columnDefinition
     */
    private function synchronizeInlineField(
        SynchronizerContext $context,
        array $defaultRecord,
        array $translatedRecord,
        string $columnName,
        array $columnDefinition,
    ): void {
        $inlineTable = $columnDefinition['config']['foreign_table'];
        $inlineField = $columnDefinition['config']['foreign_field'];
        $inlineChilds = $this->getInlineChilds(
            $inlineTable,
            $inlineField,
            $defaultRecord['uid'],
            $context->defaultLanguage->getLanguageId(),
        );
        if ($inlineChilds === null) {
            // No inline children. Skip to next loop iteration.
            return;
        }
        while ($inlineChild = $inlineChilds->fetchAssociative()) {
            $this->synchronizeRecord(
                $context->withRecord($inlineTable, $inlineChild['uid']),
                [
                    (string)$inlineField => $translatedRecord['uid'],
                ],
            );
        }
    }

    /**
     * @return Result|null
     */
    private function getInlineChilds(
        string $tableName,
        string $field,
        int $uid,
        int $defaultLanguageId,
    ): ?Result {
        $tcaCtrl = $GLOBALS['TCA'][$tableName]['ctrl'];
        if (!isset($tcaCtrl['languageField'])) {
            return null;
        }
        $queryBuilder = $this->getQueryBuilder($tableName);
        $queryBuilder->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    $field,
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $tcaCtrl['languageField'],
                    $queryBuilder->createNamedParameter($defaultLanguageId, Connection::PARAM_INT)
                )
            );

        return $queryBuilder->executeQuery();
    }

    /**
     * Get a query builder for a table.
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @return QueryBuilder
     */
    private function getQueryBuilder(string $table): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }

    /**
     * @param array<string, mixed> $defaultRecord
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function getValuesForNewRecordTranslation(
        SynchronizerContext $context,
        array $defaultRecord,
        int $languageId,
        array $values = [],
    ): array {
        $tableConfiguration = $context->tableConfiguration;
        $excludeColumns = $this->enrichExcludesColumnsForNewRecordTranslation($tableConfiguration, array_keys($values));
        foreach ($defaultRecord as $columnName => $value) {
            if (in_array($columnName, $excludeColumns, true)) {
                continue;
            }
            $values[$columnName] = $value;
        }
        if ($tableConfiguration->languageFieldName) {
            $values[$tableConfiguration->languageFieldName] = $languageId;
        }
        if ($tableConfiguration->createdAtFieldName) {
            $values[$tableConfiguration->createdAtFieldName] = $GLOBALS['EXEC_TIME'];
        }
        if ($tableConfiguration->updatedAtFieldName) {
            $values[$tableConfiguration->updatedAtFieldName] = $GLOBALS['EXEC_TIME'];
        }
        if ($tableConfiguration->transOrigPointerFieldName) {
            $values[$tableConfiguration->transOrigPointerFieldName] = $defaultRecord['uid'];
        }
        if ($tableConfiguration->translationSourceFieldName) {
            $values[$tableConfiguration->translationSourceFieldName] = $defaultRecord['uid'];
        }
        return $values;
    }

    /**
     * @param TableConfiguration $tableConfiguration
     * @param string[] $excludedColumns
     * @return string[]
     */
    private function enrichExcludesColumnsForNewRecordTranslation(
        TableConfiguration $tableConfiguration,
        array $excludedColumns,
    ): array {
        $defaultExcludedColumns = [
            'uid',
            't3ver_oid',
            't3ver_wsid',
            't3ver_state',
            't3ver_stage',
        ];
        if ($tableConfiguration->translationSourceFieldName) {
            $defaultExcludedColumns[] = $tableConfiguration->translationSourceFieldName;
        }
        $excludedColumns = array_unique(
            [
                ...array_values($defaultExcludedColumns),
                ...array_values($excludedColumns),
            ],
        );
        foreach ($tableConfiguration->columns() as $columnName => $columnDefinition) {
            $type = $columnDefinition['type'] ?? null;
            if ($type === 'inline') {
                // @todo Simply skipping inline fields is really the way to go ?
                $excludedColumns[] = $columnName;
                continue;
            }
        }
        return $excludedColumns;
    }

    /**
     * @return string[]|null
     */
    private function getColumnNamedForTranslatedRecordUpdate(SynchronizerContext $context): ?array
    {
        $updateColumns = [];
        foreach ($context->tableConfiguration->columns() as $columnName => $columnDefinition) {
            if (!$this->columnIsUsableForUpdate($columnDefinition)) {
                continue;
            }
            $updateColumns[] = $columnName;
        }
        return $updateColumns ?: null;
    }

    /**
     * @param array<string, mixed> $columnDefinition
     * @return bool
     */
    private function columnIsUsableForUpdate(array $columnDefinition): bool
    {
        return isset($columnDefinition['config']['type'])
            && is_string($columnDefinition['config']['type'])
            && $columnDefinition['config']['type'] !== 'inline'
            && isset($columnDefinition['l10n_mode'])
            && $columnDefinition['l10n_mode'] === 'exclude'
        ;
    }
}
