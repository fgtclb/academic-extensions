<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Service;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\Validation;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Builds the inline view model in the exact order of documentSections.
 */
final class ProfileDocumentSectionProvider
{
    public function __construct(
        private readonly AcademicPersonsSettings $academicPersonsSettings,
    ) {}

    /**
     * @return list<array{
     *     identifier: string,
     *     fieldName: string,
     *     type: string,
     *     label: string,
     *     kind: 'contract'|'profileInformation',
     *     dateMode: 'date'|'range'|'year'|'start',
     *     readOnly: bool,
     *     validations: array<string, Validation>,
     *     position: int,
     *     items: list<Contract|ProfileInformation>
     * }>
     */
    public function getSections(Profile $profile): array
    {
        $sections = [];
        foreach ($this->academicPersonsSettings->documentSections as $section) {
            $contractSection = $section->isContractSection();
            $sections[] = [
                'identifier' => $section->identifier,
                'fieldName' => $section->fieldName,
                'type' => $section->type,
                'label' => $section->label,
                'kind' => $contractSection ? 'contract' : 'profileInformation',
                'dateMode' => $contractSection ? 'date' : $this->getDateMode($section),
                'readOnly' => $section->readOnly,
                'validations' => $section->validationSet->validations,
                'position' => $section->position,
                'items' => $contractSection
                    ? $this->getContractItems($profile)
                    : $this->getProfileInformationItems($profile, $section),
            ];
        }
        return $sections;
    }

    /**
     * @return list<Contract>
     */
    private function getContractItems(Profile $profile): array
    {
        return array_values(array_filter(
            $profile->getContracts()->toArray(),
            static fn(mixed $item): bool => $item instanceof Contract,
        ));
    }

    /**
     * @return list<ProfileInformation>
     */
    private function getProfileInformationItems(Profile $profile, DocumentSection $section): array
    {
        $getter = 'get' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $section->fieldName)));
        if (!is_callable([$profile, $getter])) {
            return [];
        }
        $items = $profile->{$getter}();
        if (!$items instanceof ObjectStorage) {
            return [];
        }
        return array_values(array_filter(
            $items->toArray(),
            static fn(mixed $item): bool => $item instanceof ProfileInformation,
        ));
    }

    /**
     * @return 'range'|'year'|'start'
     */
    private function getDateMode(DocumentSection $section): string
    {
        return match ($section->type) {
            'cooperation', 'membership', 'scientific_research' => 'range',
            'curriculum_vitae' => 'start',
            default => 'year',
        };
    }
}
