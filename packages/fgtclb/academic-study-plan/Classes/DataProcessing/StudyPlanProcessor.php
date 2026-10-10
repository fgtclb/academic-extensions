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
 *
 * It also hands the template `idPrefix`, which the dialog ids of the plan start with:
 * empty for a plan placed on the page, `c<uid>-` for a plan rendered inside another
 * content element, an "Insert records" element for example.
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
        $processedData['idPrefix'] = $this->idPrefix($cObj);
        return $processedData;
    }

    /**
     * The prefix of the dialog ids, from the content element the plan is rendered inside.
     *
     * An "Insert records" element renders the same content element again, with the same
     * module uids and therefore the same dialog ids as the original. `RECORDS` and
     * `CONTENT` hand every record they render the request of the content object they
     * belong to, and its `currentContentObject` attribute is the renderer of the element
     * they are rendered from: `tt_content:<uid>` for an "Insert records" element,
     * `pages:<uid>` or a renderer without a record for a column of the page. A plan on the
     * page therefore keeps its ids, and every copy rendered by another content element
     * gets ids of its own. The uid is that of the record after the language overlay, so a
     * translated page in connected mode carries the ids of its default language page.
     *
     * The plan's own renderer never counts: were it ever handed a request that names
     * itself, every plan would be prefixed with its own uid, on the page as well.
     *
     * `getRequest()` is `@internal` on TYPO3 v13 and v14, and it is the only way to the
     * renderer a content element is rendered from: the parent record is protected on v14.
     * The content objects that render a record set the request, and where none is set the
     * core has already called `getRequest()` on the same renderer before this processor
     * runs, so it adds no deprecation of its own. The script finds the dialog of its own
     * plan without the prefix as well, so losing it would only repeat ids, not open a
     * wrong dialog.
     */
    private function idPrefix(ContentObjectRenderer $cObj): string
    {
        $renderer = $cObj->getRequest()->getAttribute('currentContentObject');
        if (!$renderer instanceof ContentObjectRenderer
            || $renderer === $cObj
            || !str_starts_with($renderer->currentRecord, 'tt_content:')
        ) {
            return '';
        }
        $uid = (int)($renderer->data['uid'] ?? 0);

        return $uid > 0 ? 'c' . $uid . '-' : '';
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
