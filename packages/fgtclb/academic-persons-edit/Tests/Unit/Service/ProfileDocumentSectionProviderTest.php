<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Service;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\ValidationSet;
use FGTCLB\AcademicPersonsEdit\Service\ProfileDocumentSectionProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProfileDocumentSectionProviderTest extends UnitTestCase
{
    #[Test]
    public function relationFieldNameControlsDomainMappingIndependentlyOfTheSectionIdentifier(): void
    {
        $profile = new Profile();
        $vita = new ProfileInformation();
        $profile->getVita()->attach($vita);
        $settings = new AcademicPersonsSettings(
            documentSections: [
                'career' => new DocumentSection(
                    identifier: 'career',
                    fieldName: 'vita',
                    type: 'curriculum_vitae',
                    label: 'Career',
                    readOnly: false,
                    validationSet: new ValidationSet(identifier: 'career', validations: []),
                    position: 0,
                ),
            ],
        );
        $sections = (new ProfileDocumentSectionProvider($settings))->getSections($profile);
        $this->assertSame([$vita], $sections[0]['items']);
    }

    #[Test]
    public function sectionsFollowAcademicPersonsSettingsAndPreserveTypedItems(): void
    {
        $profile = new Profile();
        $contract = new Contract();
        $vita = new ProfileInformation();
        $lecture = new ProfileInformation();
        $cooperation = new ProfileInformation();
        $profile->getContracts()->attach($contract);
        $profile->getVita()->attach($vita);
        $profile->getLectures()->attach($lecture);
        $profile->getCooperation()->attach($cooperation);
        $settings = new AcademicPersonsSettings(
            documentSections: [
                'contracts' => new DocumentSection(
                    identifier: 'contracts',
                    fieldName: 'contracts',
                    type: 'contracts',
                    label: 'LLL:EXT:academic_persons:contracts',
                    readOnly: true,
                    validationSet: new ValidationSet(identifier: 'contracts', validations: []),
                    position: 0,
                ),
                'vita' => new DocumentSection(
                    identifier: 'vita',
                    fieldName: 'vita',
                    type: 'curriculum_vitae',
                    label: 'LLL:EXT:academic_persons:vita',
                    readOnly: false,
                    validationSet: new ValidationSet(identifier: 'vita', validations: []),
                    position: 1,
                ),
                'lectures' => new DocumentSection(
                    identifier: 'lectures',
                    fieldName: 'lectures',
                    type: 'lecture',
                    label: 'LLL:EXT:academic_persons:lectures',
                    readOnly: false,
                    validationSet: new ValidationSet(identifier: 'lectures', validations: []),
                    position: 2,
                ),
                'cooperation' => new DocumentSection(
                    identifier: 'cooperation',
                    fieldName: 'cooperation',
                    type: 'cooperation',
                    label: 'LLL:EXT:academic_persons:cooperation',
                    readOnly: false,
                    validationSet: new ValidationSet(identifier: 'cooperation', validations: []),
                    position: 3,
                ),
            ],
            raw: [],
        );
        $sections = (new ProfileDocumentSectionProvider($settings))->getSections($profile);
        $this->assertSame(
            ['contracts', 'vita', 'lectures', 'cooperation'],
            array_column($sections, 'identifier'),
        );
        $this->assertSame(
            ['contracts', 'vita', 'lectures', 'cooperation'],
            array_column($sections, 'fieldName'),
        );
        $this->assertSame(
            ['contracts', 'curriculum_vitae', 'lecture', 'cooperation'],
            array_column($sections, 'type'),
        );
        $this->assertSame(
            [
                'LLL:EXT:academic_persons:contracts',
                'LLL:EXT:academic_persons:vita',
                'LLL:EXT:academic_persons:lectures',
                'LLL:EXT:academic_persons:cooperation',
            ],
            array_column($sections, 'label'),
        );
        $this->assertSame(range(0, 3), array_column($sections, 'position'));
        $this->assertSame(
            ['contract', 'profileInformation', 'profileInformation', 'profileInformation'],
            array_column($sections, 'kind'),
        );
        $this->assertSame([true, false, false, false], array_column($sections, 'readOnly'));
        $this->assertSame(
            ['date', 'start', 'year', 'range'],
            array_column($sections, 'dateMode'),
        );
        $this->assertSame([$contract], $sections[0]['items']);
        $this->assertSame([$vita], $sections[1]['items']);
        $this->assertSame([$lecture], $sections[2]['items']);
        $this->assertSame([$cooperation], $sections[3]['items']);
    }
}
