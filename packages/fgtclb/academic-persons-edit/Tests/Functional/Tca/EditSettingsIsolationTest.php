<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Tca;

use FGTCLB\AcademicPersonsEdit\Tests\Functional\AbstractAcademicPersonsEditTestCase;
use PHPUnit\Framework\Attributes\Test;

final class EditSettingsIsolationTest extends AbstractAcademicPersonsEditTestCase
{
    #[Test]
    public function frontendDocumentRequirementsNeverChangeBackendTca(): void
    {
        $table = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information'];
        foreach (['year', 'year_start', 'year_end'] as $fieldName) {
            $config = $table['columns'][$fieldName]['config'];
            $this->assertSame('datetime', $config['type']);
            $this->assertSame('date', $config['format']);
            $this->assertSame('date', $config['dbType']);
            $this->assertTrue($config['nullable']);
            $this->assertArrayNotHasKey('required', $config);
        }
        $this->assertArrayNotHasKey('columnsOverrides', $table['types']['cooperation']);
    }

    #[Test]
    public function frontendProfileAndContactRequirementsNeverChangeBackendTca(): void
    {
        $profile = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];
        $this->assertArrayNotHasKey('required', $profile['gender']['config']);
        $this->assertTrue($profile['first_name']['config']['required']);
        $this->assertArrayNotHasKey('readOnly', $profile['first_name']['config']);

        $email = $GLOBALS['TCA']['tx_academicpersons_domain_model_email']['columns']['email']['config'];
        $this->assertSame('input', $email['type']);
        $this->assertArrayNotHasKey('required', $email);

        $address = $GLOBALS['TCA']['tx_academicpersons_domain_model_address']['columns'];
        $this->assertArrayNotHasKey('required', $address['street']['config']);
        $this->assertArrayNotHasKey('required', $address['city']['config']);
        $this->assertTrue($address['zip']['config']['required']);
    }
}
