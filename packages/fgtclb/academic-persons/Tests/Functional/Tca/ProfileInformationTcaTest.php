<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ProfileInformationTcaTest extends AbstractAcademicPersonsTestCase
{
    /**
     * The three date columns are native SQL DATE columns whose FormEngine
     * control is the date picker. The `[required, date]` flag list of every
     * document section of the shipped `Settings.yaml` reaches the table as a
     * `columnsOverrides` fragment of that section's record type, and that
     * merge must not replace the column type: the `date` flag sets the
     * frontend input type only, and a section's `required` stays with its
     * type rather than landing on the column all seven types share.
     */
    #[Test]
    public function nativeDateTcaIsIndependentFromFrontendValidation(): void
    {
        $table = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information'];
        foreach (['date', 'date_start', 'date_end'] as $fieldName) {
            $this->assertSame('datetime', $table['columns'][$fieldName]['config']['type'], $fieldName);
            $this->assertSame('date', $table['columns'][$fieldName]['config']['format'], $fieldName);
            $this->assertSame('date', $table['columns'][$fieldName]['config']['dbType'], $fieldName);
            $this->assertTrue($table['columns'][$fieldName]['config']['nullable'], $fieldName);
            $this->assertArrayNotHasKey('required', $table['columns'][$fieldName]['config'], $fieldName);
        }
        $this->assertSame('check', $table['columns']['year_only']['config']['type']);
        $override = $table['types']['publication']['columnsOverrides']['date']['config'];
        $this->assertTrue($override['required'], 'the required flag of Settings.yaml reaches the record type');
        $this->assertArrayNotHasKey('type', $override, 'the date flag does not touch the column type');
        $this->assertArrayNotHasKey('format', $override);
        $this->assertArrayNotHasKey('dbType', $override);
    }

    /**
     * The seven relations of a profile to its information records are part of
     * the domain model. They used to be generated from the settings file, so a
     * settings override without the entry silently lost the backend column;
     * now they are declared by the TCA file and exist whatever the settings say.
     */
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
            $this->assertSame('inline', $columns[$fieldName]['config']['type'], $fieldName);
            $this->assertSame(
                'tx_academicpersons_domain_model_profile_information',
                $columns[$fieldName]['config']['foreign_table'],
                $fieldName,
            );
            $this->assertSame($recordType, $columns[$fieldName]['config']['foreign_match_fields']['type'], $fieldName);
            $this->assertSame(
                $recordType,
                $columns[$fieldName]['config']['overrideChildTca']['columns']['type']['config']['default'],
                $fieldName,
            );
            $this->assertSame(
                'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.' . $fieldName . '.label',
                $columns[$fieldName]['label'],
                $fieldName,
            );
        }
    }
}
