<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Tca;

use FGTCLB\AcademicPersonsEdit\Tests\Functional\AbstractAcademicPersonsEditTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The counterpart of `EXT:academic_persons`'
 * :php:`Tests\Functional\Tca\EditSettingsIsolationTest`, which pins that the
 * settings driven backend TCA applies **without** this extension. This one
 * loads it and asserts the same merged TCA: what it proves is that adding
 * `academic_persons_edit` to an installation changes none of it.
 */
final class EditSettingsWithEditingExtensionTest extends AbstractAcademicPersonsEditTestCase
{
    #[Test]
    public function centralDocumentValidationIsAppliedToBackendTca(): void
    {
        $table = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information'];
        $this->assertArrayHasKey('columnsOverrides', $table['types']['cooperation']);
        $overrides = $table['types']['cooperation']['columnsOverrides'];
        $this->assertTrue($overrides['date']['config']['required']);
        $this->assertFalse($overrides['date_start']['config']['required']);
        $this->assertFalse($overrides['date_end']['config']['required']);
        $this->assertSame('datetime', $table['columns']['date']['config']['type']);
        $this->assertSame('date', $table['columns']['date']['config']['format']);
        $this->assertSame('date', $table['columns']['date']['config']['dbType']);
        $this->assertTrue($table['columns']['date']['config']['nullable']);
    }

    #[Test]
    public function centralProfileAndContactValidationIsAppliedToBackendTca(): void
    {
        $profile = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];
        $this->assertTrue($profile['gender']['config']['required']);
        $this->assertSame(1, $profile['gender']['config']['minitems']);
        $this->assertFalse($profile['first_name']['config']['required']);
        $this->assertTrue($profile['first_name']['config']['readOnly']);
        $this->assertSame('check', $profile['skip_sync']['config']['type']);
        $email = $GLOBALS['TCA']['tx_academicpersons_domain_model_email']['columns']['email']['config'];
        $this->assertSame('email', $email['type']);
        $this->assertTrue($email['required']);
        $address = $GLOBALS['TCA']['tx_academicpersons_domain_model_address']['columns'];
        $this->assertTrue($address['street']['config']['required']);
        $this->assertTrue($address['street_number']['config']['required']);
        $this->assertTrue($address['city']['config']['required']);
        $this->assertTrue($address['zip']['config']['required']);
    }
}
