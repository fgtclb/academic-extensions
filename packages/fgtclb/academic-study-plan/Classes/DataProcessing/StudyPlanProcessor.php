<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\DataProcessing;

use Doctrine\DBAL\ParameterType;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Data processor for academic study plan content element
 * Fetches semesters, modules, categories, and audio files with translation support
 */
#[Autoconfigure(public: true)]
final class StudyPlanProcessor implements DataProcessorInterface
{
    // NOTE: Not injected as not DI aware service and passing context in constructor.
    // This is a service and considerable not state.
    private ?PageRepository $pageRepository = null;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly FileRepository $fileRepository,
    ) {}

    /**
     * @param array<string, mixed> $contentObjectConfiguration
     * @param array<string, mixed> $processorConfiguration
     * @param array<string, mixed> $processedData
     * @return array<string, mixed>
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $dataTableName = $cObj->getCurrentTable();
        $transOrigPointerFieldName = $this->getTableTransOrigPointerFieldName($dataTableName);
        $contentElementUid = (int)($processedData['data']['uid'] ?? 0);
        if ($contentElementUid === 0 || $transOrigPointerFieldName === null || $transOrigPointerFieldName === '') {
            return $processedData;
        }
        $context = GeneralUtility::makeInstance(Context::class);
        /** @var LanguageAspect $languageAspect */
        $languageAspect = $context->getAspect('language');
        $languageUid = $languageAspect->getContentId();
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        $originalContentElementUid = $contentElementUid;
        $l10nParent = (int)($processedData['data'][$transOrigPointerFieldName] ?? 0);
        if ($l10nParent > 0) {
            $originalContentElementUid = $l10nParent;
        }
        $processedData['semesters'] = $this->fetchSemesters($originalContentElementUid, $languageUid);
        return $processedData;
    }

    /**
     * @return list<array<string, mixed>>
     * @todo Extract into dedicated service class `StudyPlanProcessorService`.
     */
    private function fetchSemesters(int $contentElementUid, int $languageUid): array
    {
        $languageFieldName = $this->getTableLanguageFieldName('tx_academicstudyplan_domain_model_semester');
        if ($languageFieldName === null || $languageFieldName === '') {
            return [];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_academicstudyplan_domain_model_semester');
        $result = $queryBuilder
            ->select('*')
            ->from('tx_academicstudyplan_domain_model_semester')
            ->where(
                $queryBuilder->expr()->eq('content_element', $queryBuilder->createNamedParameter($contentElementUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->in($languageFieldName, [0, -1])
            )
            ->orderBy('sorting', 'ASC')
            // Ensuring deterministic sorting behaviour
            ->addOrderBy('uid', 'ASC')
            ->executeQuery();
        $semesters = [];
        while ($row = $result->fetchAssociative()) {
            $row = $this->getTranslatedRecord('tx_academicstudyplan_domain_model_semester', $row, $languageUid);
            $row['modules'] = $this->fetchModules((int)$row['uid'], $languageUid);
            $semesters[] = $row;
        }
        return $semesters;
    }

    /**
     * @return list<array<string, mixed>>
     * @todo Extract into dedicated service class `StudyPlanProcessorService`.
     */
    private function fetchModules(int $semesterUid, int $languageUid): array
    {
        $languageFieldName = $this->getTableLanguageFieldName('tx_academicstudyplan_domain_model_module');
        if ($languageFieldName === null || $languageFieldName === '') {
            return [];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_academicstudyplan_domain_model_module');
        $result = $queryBuilder
            ->select('*')
            ->from('tx_academicstudyplan_domain_model_module')
            ->where(
                $queryBuilder->expr()->eq('semester', $queryBuilder->createNamedParameter($semesterUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->in($languageFieldName, [0, -1])
            )
            ->orderBy('sorting', 'ASC')
            // Ensuring deterministic sorting behaviour
            ->addOrderBy('uid', 'ASC')
            ->executeQuery();
        $modules = [];
        while ($row = $result->fetchAssociative()) {
            $row = $this->getTranslatedRecord('tx_academicstudyplan_domain_model_module', $row, $languageUid);
            $row['categories'] = $this->fetchCategoriesForModule((int)$row['uid'], $languageUid);
            $row['audioFiles'] = $this->fetchAudioFiles((int)$row['uid']);
            $modules[] = $row;
        }
        return $modules;
    }

    /**
     * @return list<array<string, mixed>>
     * @todo Extract into dedicated service class `StudyPlanProcessorService`.
     */
    private function fetchCategoriesForModule(int $moduleUid, int $languageUid): array
    {
        $languageFieldName = $this->getTableLanguageFieldName('tx_academicstudyplan_domain_model_category');
        if ($languageFieldName === null || $languageFieldName === '') {
            return [];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_academicstudyplan_domain_model_category');
        $result = $queryBuilder
            ->select('c.*')
            ->from('tx_academicstudyplan_domain_model_category', 'c')
            ->join('c', 'tx_academicstudyplan_module_category_mm', 'mm', $queryBuilder->expr()->eq('mm.uid_foreign', 'c.uid'))
            ->where(
                $queryBuilder->expr()->eq('mm.uid_local', $queryBuilder->createNamedParameter($moduleUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->in(sprintf('%s.%s', 'c', $languageFieldName), [0, -1])
            )
            ->orderBy('c.label', 'ASC')
            // Ensuring deterministic sorting behaviour
            ->addOrderBy('c.uid', 'ASC')
            ->executeQuery();
        $categories = [];
        while ($row = $result->fetchAssociative()) {
            $row = $this->getTranslatedRecord('tx_academicstudyplan_domain_model_category', $row, $languageUid);
            $categories[] = $row;
        }
        return $categories;
    }

    /**
     * @return list<array<string, string|null>>
     * @todo Extract into dedicated service class `StudyPlanProcessorService`.
     */
    private function fetchAudioFiles(int $moduleUid): array
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
    private function getTranslatedRecord(string $table, array $row, int $languageUid): array
    {
        if ($languageUid === 0 || $this->pageRepository === null) {
            return $row;
        }
        return $this->pageRepository->getLanguageOverlay($table, $row) ?? $row;
    }

    private function getTableTransOrigPointerFieldName(string $tableName): ?string
    {
        if (isset($GLOBALS['TCA'][$tableName])
            && is_array($GLOBALS['TCA'][$tableName])
            && isset($GLOBALS['TCA'][$tableName]['ctrl'])
            && is_array($GLOBALS['TCA'][$tableName]['ctrl'])
            && isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
            && is_string($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
            && trim($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']) !== ''
        ) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'];
        }
        return null;
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
}
