<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Service;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class StudyPlanService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly FileRepository $fileRepository,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchSemesters(int $contentElementUid, int $languageUid, ?PageRepository $pageRepository = null, ?Context $context = null): array
    {
        $context ??= GeneralUtility::makeInstance(Context::class);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_academicstudyplan_domain_model_semester');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class)->removeByType(DeletedRestriction::class);
        $result = $queryBuilder
            ->select('*')
            ->from('tx_academicstudyplan_domain_model_semester')
            ->where(
                $queryBuilder->expr()->eq('content_element', $queryBuilder->createNamedParameter($contentElementUid, Connection::PARAM_INT)),
                $this->getLanguageFieldRestrictionForTable($queryBuilder, $context, 'tx_academicstudyplan_domain_model_semester'),
                $this->getHiddenRestrictionForTable($queryBuilder, $context, 'tx_academicstudyplan_domain_model_semester'),
                $this->getDeletedRestrictionForTable($queryBuilder, $context, 'tx_academicstudyplan_domain_model_semester'),
            )
            ->orderBy('sorting', 'ASC')
            // Ensuring deterministic sorting behaviour
            ->addOrderBy('uid', 'ASC')
            ->executeQuery();
        $pageRepository ??= GeneralUtility::makeInstance(PageRepository::class);
        $semesters = [];
        while ($row = $result->fetchAssociative()) {
            $row = $this->getTranslatedRecord('tx_academicstudyplan_domain_model_semester', $row, $languageUid, $pageRepository);
            $row['modules'] = $this->fetchModules((int)$row['uid'], $languageUid, $pageRepository, $context);
            $semesters[] = $row;
        }
        return $semesters;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchModules(int $semesterUid, int $languageUid, ?PageRepository $pageRepository = null, ?Context $context = null): array
    {
        $context ??= GeneralUtility::makeInstance(Context::class);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_academicstudyplan_domain_model_module');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class)->removeByType(DeletedRestriction::class);
        $result = $queryBuilder
            ->select('*')
            ->from('tx_academicstudyplan_domain_model_module')
            ->where(
                $queryBuilder->expr()->eq('semester', $queryBuilder->createNamedParameter($semesterUid, Connection::PARAM_INT)),
                $this->getLanguageFieldRestrictionForTable($queryBuilder, $context, 'tx_academicstudyplan_domain_model_module'),
                $this->getHiddenRestrictionForTable($queryBuilder, $context, 'tx_academicstudyplan_domain_model_module'),
                $this->getDeletedRestrictionForTable($queryBuilder, $context, 'tx_academicstudyplan_domain_model_module'),
            )
            ->orderBy('sorting', 'ASC')
            // Ensuring deterministic sorting behaviour
            ->addOrderBy('uid', 'ASC')
            ->executeQuery();
        $pageRepository ??= GeneralUtility::makeInstance(PageRepository::class);
        $modules = [];
        while ($row = $result->fetchAssociative()) {
            $row = $this->getTranslatedRecord('tx_academicstudyplan_domain_model_module', $row, $languageUid, $pageRepository);
            $row['categories'] = $this->fetchCategoriesForModule((int)$row['uid'], $languageUid, $pageRepository, $context);
            $row['audioFiles'] = $this->fetchAudioFiles((int)$row['uid']);
            $modules[] = $row;
        }
        return $modules;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchCategoriesForModule(int $moduleUid, int $languageUid, ?PageRepository $pageRepository = null, ?Context $context = null): array
    {
        $context ??= GeneralUtility::makeInstance(Context::class);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_academicstudyplan_domain_model_category');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class)->removeByType(DeletedRestriction::class);
        $result = $queryBuilder
            ->select('c.*')
            ->from('tx_academicstudyplan_domain_model_category', 'c')
            ->join('c', 'tx_academicstudyplan_module_category_mm', 'mm', $queryBuilder->expr()->eq('mm.uid_foreign', 'c.uid'))
            // @todo Consider to add additional join to `*_module` along with language/hidden/deleted constraints below
            ->where(
                $queryBuilder->expr()->eq('mm.uid_local', $queryBuilder->createNamedParameter($moduleUid, Connection::PARAM_INT)),
                $this->getLanguageFieldRestrictionForTable($queryBuilder, $context, 'tx_academicstudyplan_domain_model_category', 'c'),
                $this->getHiddenRestrictionForTable($queryBuilder, $context, 'tx_academicstudyplan_domain_model_category', 'c'),
                $this->getDeletedRestrictionForTable($queryBuilder, $context, 'tx_academicstudyplan_domain_model_category', 'c'),
                // @todo Consider to add `*_module` constraints for language/hidden/deleted if join is added above
            )
            ->orderBy('c.label', 'ASC')
            // Ensuring deterministic sorting behaviour
            ->addOrderBy('c.uid', 'ASC')
            ->executeQuery();
        $pageRepository ??= GeneralUtility::makeInstance(PageRepository::class, $context);
        $categories = [];
        while ($row = $result->fetchAssociative()) {
            $row = $this->getTranslatedRecord('tx_academicstudyplan_domain_model_category', $row, $languageUid, $pageRepository);
            $categories[] = $row;
        }
        return $categories;
    }

    /**
     * @return list<array<string, string|null>>
     */
    public function fetchAudioFiles(int $moduleUid): array
    {
        $fileReferences = $this->fileRepository->findByRelation(
            'tx_academicstudyplan_domain_model_module',
            'audio_file',
            $moduleUid
        );
        $files = [];
        foreach ($fileReferences as $fileReference) {
            $files[] = [
                'publicUrl' => $fileReference->getPublicUrl(),
                'mimeType' => $fileReference->getMimeType(),
                'title' => $fileReference->getTitle(),
                'description' => $fileReference->getDescription(),
            ];
        }
        return $files;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function getTranslatedRecord(string $table, array $row, int $languageUid, PageRepository $pageRepository): array
    {
        if ($languageUid === 0) {
            return $row;
        }
        return $pageRepository->getLanguageOverlay($table, $row) ?? $row;
    }

    private function getTableLanguageFieldName(string $tableName): ?string
    {
        if (isset($GLOBALS['TCA'][$tableName])
            && is_array($GLOBALS['TCA'][$tableName])
            && isset($GLOBALS['TCA'][$tableName]['ctrl'])
            && is_array($GLOBALS['TCA'][$tableName]['ctrl'])
            && isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
            && is_string($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
            && trim($GLOBALS['TCA'][$tableName]['ctrl']['languageField']) !== ''
        ) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];
        }
        return null;
    }

    private function getTableHiddenFieldName(string $tableName): ?string
    {
        if (isset($GLOBALS['TCA'][$tableName])
            && is_array($GLOBALS['TCA'][$tableName])
            && isset($GLOBALS['TCA'][$tableName]['ctrl'])
            && is_array($GLOBALS['TCA'][$tableName]['ctrl'])
            && isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])
            && is_array($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])
            && isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'])
            && is_string($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'])
            && trim((string)$GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled']) !== ''
        ) {
            return trim((string)($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'])) ?: null;
        }
        return null;
    }
    private function getTableDeletedFieldName(string $tableName): ?string
    {
        if (isset($GLOBALS['TCA'][$tableName])
            && is_array($GLOBALS['TCA'][$tableName])
            && isset($GLOBALS['TCA'][$tableName]['ctrl'])
            && is_array($GLOBALS['TCA'][$tableName]['ctrl'])
            && isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])
            && is_array($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])
            && isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['deleted'])
            && is_string($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['deleted'])
            && trim((string)$GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['deleted']) !== ''
        ) {
            return trim((string)($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['deleted'])) ?: null;
        }
        return null;
    }

    private function getLanguageFieldRestrictionForTable(
        QueryBuilder $queryBuilder,
        Context $context,
        string $tableName,
        ?string $alias = null,
    ): string {
        $languageFieldName = $this->getTableLanguageFieldName('tx_academicstudyplan_domain_model_semester');
        if ($languageFieldName === null || $languageFieldName === '') {
            // return empty string to omit condition
            return '';
        }
        $prefix = ($alias ? $alias . '.' : '');
        return $queryBuilder->expr()->in($prefix . $languageFieldName, [0, -1]);
    }

    private function getDeletedRestrictionForTable(
        QueryBuilder $queryBuilder,
        Context $context,
        string $tableName,
        ?string $alias = null,
    ): string {
        $deletedFieldName = $this->getTableDeletedFieldName($tableName);
        if ($deletedFieldName === null || $deletedFieldName === '') {
            // return empty string to omit condition
            return '';
        }
        $prefix = ($alias ? $alias . '.' : '');
        $includeDeletedRecords = $context->getPropertyFromAspect('visibility', 'includeDeletedRecords', false);
        if ($includeDeletedRecords) {
            return $queryBuilder->expr()->in($prefix . $deletedFieldName, [0, 1]);
        }
        return $queryBuilder->expr()->eq($prefix . $deletedFieldName, 0);
    }

    private function getHiddenRestrictionForTable(
        QueryBuilder $queryBuilder,
        Context $context,
        string $tableName,
        ?string $alias = null
    ): string {
        $hiddenFieldName = $this->getTableHiddenFieldName($tableName);
        if ($hiddenFieldName === null || $hiddenFieldName === '') {
            // return empty string to omit condition
            return '';
        }
        $prefix = ($alias ? $alias . '.' : '');
        $includeHiddenContent = $context->getPropertyFromAspect('visibility', 'includeHiddenContent', false);
        if ($includeHiddenContent) {
            return $queryBuilder->expr()->in($prefix . $hiddenFieldName, [0, 1]);
        }
        return $queryBuilder->expr()->eq($prefix . $hiddenFieldName, 0);
    }
}
