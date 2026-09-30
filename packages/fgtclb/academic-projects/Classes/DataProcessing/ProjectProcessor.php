<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\DataProcessing;

use FGTCLB\AcademicProjects\Factory\ProjectFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;

/**
 * Processor class for project page types
 */
class ProjectProcessor implements DataProcessorInterface
{
    /**
     * Make project data accessable in Fluid
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<string, mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<string, mixed> $processorConfiguration The configuration of this processor
     * @param array<string, mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array<string, mixed> the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        // The page record: the one of the page information object "page" a PAGEVIEW page
        // object assigns, or "data" of a FLUIDTEMPLATE page object. "page" first, because
        // PAGEVIEW reserves that name, while a PAGEVIEW site package may assign a "data" of
        // its own - the page template resolves it in the same order.
        $page = $processedData['page'] ?? null;
        $pageData = $page instanceof PageInformation ? $page->getPageRecord() : ($processedData['data'] ?? []);
        if (is_array($pageData) && $pageData !== []) {
            $programDataFactory = GeneralUtility::makeInstance(ProjectFactory::class);
            $processedData['project'] = $programDataFactory->get($pageData);
        }
        return $processedData;
    }
}
