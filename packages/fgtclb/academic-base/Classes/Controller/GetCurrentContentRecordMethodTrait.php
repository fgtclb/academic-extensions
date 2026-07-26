<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Controller;

use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Provides method {@see self::getCurrentContentRecord()} to be used in extbase action controllers to assign
 * the `record` view variable next to the already assigned `data` array.
 *
 * TYPO3 v14 rewrote the `EXT:fluid_styled_content` header partial: `Header/All.fluid.html` renders the header
 * and subheader with `{record -> f:render.text(...)}` instead of reading `{data.header}`, and that ViewHelper
 * requires a record object. Content elements based on `lib.contentElement` get it from the
 * `record-transformation` data processor, but an extbase plugin view assigns only `data`, so templates
 * rendering that partial fail with an exception on TYPO3 v14 unless the record is provided.
 *
 * TYPO3 v13 ignores the variable, its header partial reads `data`, so assigning it is version agnostic.
 *
 * @api considered API and to be used in projects or extension extending academic extensions.
 */
trait GetCurrentContentRecordMethodTrait
{
    /**
     * Builds the record of the current content element to be assigned as `record` view variable.
     *
     * Pass the content object renderer the controller already uses to determine the `data` array, for
     * example `$this->getCurrentContentRecord($this->getCurrentContentObjectRenderer())`.
     *
     * `RecordFactory` is identical in TYPO3 v13 and v14, therefore no core version aware handling is needed.
     */
    protected function getCurrentContentRecord(?ContentObjectRenderer $contentObjectRenderer): ?RecordInterface
    {
        $row = $contentObjectRenderer?->data;
        if (!is_array($row) || $row === []) {
            return null;
        }

        return GeneralUtility::makeInstance(RecordFactory::class)
            ->createResolvedRecordFromDatabaseRow('tt_content', $row);
    }
}
