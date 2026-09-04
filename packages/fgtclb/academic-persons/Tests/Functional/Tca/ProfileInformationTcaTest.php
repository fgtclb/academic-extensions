<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ProfileInformationTcaTest extends AbstractAcademicPersonsTestCase
{
    /**
     * The three date columns are native SQL DATE columns whose FormEngine
     * control is the date picker. The `[required, date]` flag list of the
     * shipped `Settings.yaml` is merged into the `date` column's config by the
     * TCA file, and that merge must not replace the column type: the `date`
     * flag sets the frontend input type only.
     */
    #[Test]
    public function nativeDateTcaIsIndependentFromFrontendValidation(): void
    {
        $columns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information']['columns'];
        foreach (['date', 'date_start', 'date_end'] as $fieldName) {
            $this->assertSame('datetime', $columns[$fieldName]['config']['type'], $fieldName);
            $this->assertSame('date', $columns[$fieldName]['config']['format'], $fieldName);
            $this->assertSame('date', $columns[$fieldName]['config']['dbType'], $fieldName);
            $this->assertTrue($columns[$fieldName]['config']['nullable'], $fieldName);
        }
        $this->assertTrue($columns['date']['config']['required'], 'the required flag of Settings.yaml reaches the column');
        $this->assertSame('check', $columns['year_only']['config']['type']);
    }
}
