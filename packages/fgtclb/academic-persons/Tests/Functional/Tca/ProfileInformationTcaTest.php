<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ProfileInformationTcaTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function domainRelationsRemainAvailableWithoutEditSettings(): void
    {
        $columns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];
        $expectedRelations = [
            'scientific_research' => 'scientific_research',
            'vita' => 'curriculum_vitae',
            'memberships' => 'membership',
            'cooperation' => 'cooperation',
            'publications' => 'publication',
            'lectures' => 'lecture',
            'press_media' => 'press_media',
        ];
        foreach ($expectedRelations as $fieldName => $recordType) {
            $this->assertSame('inline', $columns[$fieldName]['config']['type']);
            $this->assertSame(
                $recordType,
                $columns[$fieldName]['config']['foreign_match_fields']['type'],
            );
        }
    }

    #[Test]
    public function nativeDateTcaIsIndependentFromFrontendValidation(): void
    {
        $columns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information']['columns'];
        foreach (['year', 'year_start', 'year_end'] as $fieldName) {
            $this->assertSame('datetime', $columns[$fieldName]['config']['type']);
            $this->assertSame('date', $columns[$fieldName]['config']['format']);
            $this->assertSame('date', $columns[$fieldName]['config']['dbType']);
            $this->assertTrue($columns[$fieldName]['config']['nullable']);
        }
    }
}
