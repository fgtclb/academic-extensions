<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ProfileInformationTcaTest extends AbstractAcademicPersonsTestCase
{
    /**
     * The three date columns are native SQL `DATE` columns: `dbType => 'date'`
     * is what keeps the column a `DATE` and the stored value a `YYYY-MM-DD`
     * string, so neither a timestamp nor a timezone conversion is involved.
     * `type => 'datetime'` with `format => 'date'` is what renders the date
     * only control, and `nullable` is what lets the column stay empty. All of
     * it behaves identically on TYPO3 v13 and v14.
     *
     * The columns carry no `range`: a four digit bound is a property of the
     * integer years the columns used to be, and a date is not clamped here.
     */
    #[Test]
    public function dateColumnsAreNativeNullableDateColumns(): void
    {
        $table = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information'];
        foreach (['date', 'date_start', 'date_end'] as $fieldName) {
            $config = $table['columns'][$fieldName]['config'];
            $this->assertSame('date', $config['dbType'], $fieldName);
            $this->assertSame('datetime', $config['type'], $fieldName);
            $this->assertSame('date', $config['format'], $fieldName);
            $this->assertTrue($config['nullable'], $fieldName);
            $this->assertNull($config['default'], $fieldName);
            $this->assertArrayNotHasKey('range', $config, $fieldName);
            $this->assertArrayNotHasKey('required', $config, $fieldName);
        }
    }

    /**
     * The `[required, date]` flag list of every document section of the shipped
     * `Settings.yaml` reaches the table as a `columnsOverrides` fragment of
     * that section's record type, keyed by the column the section's `date`
     * field writes. So a section's `required` stays with its type rather than
     * landing on the column all seven types share.
     *
     * The `date` flag itself chooses the frontend control and nothing else: it
     * restates neither the column type, nor the `dbType` that keeps the column
     * a native `DATE`, nor the format - all three stay what the TCA file above
     * declares, which is what a `number` flag on a date column would break.
     */
    #[Test]
    public function aSectionRequiredFlagLandsOnItsRecordTypeAndNotOnTheSharedColumns(): void
    {
        $table = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information'];
        $override = $table['types']['publication']['columnsOverrides']['date']['config'];
        $this->assertTrue($override['required'], 'the required flag of Settings.yaml reaches the record type');
        $this->assertArrayNotHasKey('type', $override, 'the date flag does not restate the column type');
        $this->assertArrayNotHasKey('dbType', $override, 'the date flag does not restate the dbType');
        $this->assertArrayNotHasKey('format', $override, 'the date flag does not restate the format');
        $this->assertFalse($table['types']['publication']['columnsOverrides']['date_start']['config']['required']);
        $this->assertFalse($table['types']['publication']['columnsOverrides']['date_end']['config']['required']);
        foreach (['date', 'date_start', 'date_end'] as $fieldName) {
            $this->assertArrayNotHasKey('required', $table['columns'][$fieldName]['config'], $fieldName);
        }
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
