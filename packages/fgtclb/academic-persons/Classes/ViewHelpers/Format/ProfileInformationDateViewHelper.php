<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\ViewHelpers\Format;

use FGTCLB\AcademicBase\Date\DateDisplay;
use FGTCLB\AcademicBase\Date\LocalizedDateFormatter;
use FGTCLB\AcademicBase\ViewHelpers\Format\LocalizedDateViewHelper;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;

/**
 * Renders one date of a timeline entry, showing the parts its section publishes
 * and formatting them for the locale of the matched site language.
 *
 * A template that renders a timeline entry has the record, not the settings, so
 * this ViewHelper resolves the display configuration itself: the record type
 * names the document section, and the property name names the field within it.
 * That is why no template has to be told which parts a section publishes, and
 * why the public profile and the editor's compact rows cannot drift apart.
 *
 * An unknown record type or property falls back to showing the whole date,
 * which is what an unconfigured field shows anyway.
 *
 * Example:
 *
 * ```
 *   <ap:format.profileInformationDate date="{item.dateStart}" type="{item.type}" field="dateStart"/>
 * ```
 *
 * @internal not part of public API.
 */
final class ProfileInformationDateViewHelper extends LocalizedDateViewHelper
{
    public function __construct(
        LocalizedDateFormatter $localizedDateFormatter,
        private readonly AcademicPersonsSettings $academicPersonsSettings,
    ) {
        parent::__construct($localizedDateFormatter);
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'type',
            'string',
            'The record type of the timeline entry, which names its document section.',
            true,
        );
        $this->registerArgument(
            'field',
            'string',
            'The property of the entry: date, dateStart or dateEnd.',
            true,
        );
    }

    protected function resolveDisplay(): DateDisplay
    {
        $section = $this->academicPersonsSettings->getDocumentSectionByType((string)$this->arguments['type']);
        $validation = $section?->validationSet->get((string)$this->arguments['field']);

        return $validation?->dateSettings->display ?? parent::resolveDisplay();
    }
}
