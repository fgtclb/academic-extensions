<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\DataProcessing;

use FGTCLB\AcademicStudyPlan\Service\StudyPlanService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
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
    public function __construct(
        private readonly StudyPlanService $studyPlanService,
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
        $originalContentElementUid = $contentElementUid;
        $l10nParent = (int)($processedData['data'][$transOrigPointerFieldName] ?? 0);
        if ($l10nParent > 0) {
            $originalContentElementUid = $l10nParent;
        }
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        $processedData['semesters'] = $this->studyPlanService->fetchSemesters(
            $originalContentElementUid,
            $languageUid,
            $pageRepository,
            $context,
        );
        return $processedData;
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
}
