<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Tca;

use FGTCLB\AcademicPersonsEdit\Tests\Functional\AbstractAcademicPersonsEditTestCase;
use PHPUnit\Framework\Attributes\Test;

final class SettingsValidationOverridesTest extends AbstractAcademicPersonsEditTestCase
{
    #[Test]
    public function documentDateTcaKeepsNativeDateConfigurationWithoutEditorOverrides(): void
    {
        $columns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information']['columns'];
        foreach (['year', 'year_start', 'year_end'] as $fieldName) {
            $this->assertSame('datetime', $columns[$fieldName]['config']['type']);
            $this->assertSame('date', $columns[$fieldName]['config']['format']);
            $this->assertSame('date', $columns[$fieldName]['config']['dbType']);
            $this->assertTrue($columns[$fieldName]['config']['nullable']);
            $this->assertArrayNotHasKey('required', $columns[$fieldName]['config']);
        }
    }

    #[Test]
    public function sectionSpecificRequiredStateNeverCreatesBackendColumnsOverrides(): void
    {
        $type = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information']
            ['types']['cooperation'];
        $this->assertArrayNotHasKey('columnsOverrides', $type);
    }

    #[Test]
    public function profileAndContactTcaKeepsIndependentDomainDefaults(): void
    {
        $profileColumns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];
        $this->assertArrayNotHasKey('required', $profileColumns['gender']['config']);
        $this->assertArrayNotHasKey('readOnly', $profileColumns['first_name']['config']);
        $this->assertTrue($profileColumns['first_name']['config']['required']);
        $emailColumns = $GLOBALS['TCA']['tx_academicpersons_domain_model_email']['columns'];
        $this->assertArrayNotHasKey('required', $emailColumns['email']['config']);
        $this->assertSame('input', $emailColumns['email']['config']['type']);
        $phoneColumns = $GLOBALS['TCA']['tx_academicpersons_domain_model_phone_number']['columns'];
        $this->assertSame('input', $phoneColumns['phone_number']['config']['type']);
        $this->assertTrue($phoneColumns['phone_number']['config']['required']);
    }
}
