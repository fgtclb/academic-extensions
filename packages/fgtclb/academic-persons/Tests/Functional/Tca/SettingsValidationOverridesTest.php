<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The settings graph overlays editability and the required state; it does not
 * own the column types. `fieldType` and `renderType` of a settings entry
 * describe the frontend control, and the flags that do name a type - `email`,
 * `number` - are the only ones that reach the TCA. Everything else the TCA
 * files declare stays as declared.
 */
final class SettingsValidationOverridesTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function documentDateTcaKeepsNativeDateConfigurationWithSharedOverrides(): void
    {
        $table = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information'];
        foreach (['date', 'date_start', 'date_end'] as $fieldName) {
            $this->assertSame('datetime', $table['columns'][$fieldName]['config']['type'], $fieldName);
            $this->assertSame('date', $table['columns'][$fieldName]['config']['format'], $fieldName);
            $this->assertSame('date', $table['columns'][$fieldName]['config']['dbType'], $fieldName);
            $this->assertTrue($table['columns'][$fieldName]['config']['nullable'], $fieldName);
        }
        foreach (['cooperation', 'lecture', 'membership', 'press_media', 'publication', 'scientific_research', 'curriculum_vitae'] as $type) {
            $this->assertArrayHasKey('columnsOverrides', $table['types'][$type], $type);
            $this->assertArrayNotHasKey('type', $table['types'][$type]['columnsOverrides']['date']['config'], $type);
        }
        $this->assertArrayNotHasKey('columnsOverrides', $table['types']['contracts'] ?? []);
    }

    /**
     * The shipped sections require the title and the date of every profile
     * information type and leave the start and end dates optional; the
     * override carries the state per type, and the `html` flag of the body
     * text does not reach the column.
     */
    #[Test]
    public function sectionSpecificRequiredStateCreatesBackendColumnsOverrides(): void
    {
        $type = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information']['types']['cooperation'];

        $this->assertTrue($type['columnsOverrides']['title']['config']['required']);
        $this->assertTrue($type['columnsOverrides']['date']['config']['required']);
        $this->assertFalse($type['columnsOverrides']['date_start']['config']['required']);
        $this->assertFalse($type['columnsOverrides']['date_end']['config']['required']);
        $this->assertArrayNotHasKey('link', $type['columnsOverrides'], 'cooperation ships without a link validator');
        $lecture = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information']['types']['lecture'];
        $this->assertFalse($lecture['columnsOverrides']['link']['config']['required']);
        $this->assertArrayNotHasKey('type', $type['columnsOverrides']['bodytext']['config']);
        $this->assertArrayNotHasKey('max', $type['columnsOverrides']['bodytext']['config']);
    }

    /**
     * The profile `gender` is a `select` in the TCA file and stays one; the
     * `email` flag turns the email column into a TCA `email` type as it did
     * before the graph; the `tel` flag of the phone number is frontend only,
     * so the column keeps its `input` type; a `combinedLink` render type does
     * not turn the website column into anything but what its TCA file says.
     */
    #[Test]
    public function profileAndContactTcaKeepTheirColumnTypesAndReceiveTheRequiredState(): void
    {
        $profileColumns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];
        $this->assertSame('select', $profileColumns['gender']['config']['type']);
        $this->assertTrue($profileColumns['gender']['config']['required']);
        $this->assertTrue($profileColumns['first_name']['config']['readOnly']);
        $this->assertFalse($profileColumns['first_name']['config']['required']);
        $this->assertSame('input', $profileColumns['website']['config']['type']);
        $this->assertSame('text', $profileColumns['miscellaneous']['config']['type']);
        $this->assertTrue($profileColumns['miscellaneous']['config']['enableRichtext']);
        $this->assertArrayNotHasKey('max', $profileColumns['miscellaneous']['config']);

        $emailColumns = $GLOBALS['TCA']['tx_academicpersons_domain_model_email']['columns'];
        $this->assertSame('email', $emailColumns['email']['config']['type']);
        $this->assertTrue($emailColumns['email']['config']['required']);

        $phoneColumns = $GLOBALS['TCA']['tx_academicpersons_domain_model_phone_number']['columns'];
        $this->assertSame('input', $phoneColumns['phone_number']['config']['type']);
        $this->assertTrue($phoneColumns['phone_number']['config']['required']);

        $addressColumns = $GLOBALS['TCA']['tx_academicpersons_domain_model_address']['columns'];
        $this->assertSame('input', $addressColumns['country']['config']['type']);
        $this->assertSame('select', $addressColumns['type']['config']['type']);
    }
}
