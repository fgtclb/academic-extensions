<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\MigrateImportedPhoneNumberTypesUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;

final class MigrateImportedPhoneNumberTypesUpgradeWizardTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function isRegisteredWithDatabasePrerequisite(): void
    {
        $attributes = (new \ReflectionClass(MigrateImportedPhoneNumberTypesUpgradeWizard::class))
            ->getAttributes(UpgradeWizard::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(
            'academicPersons_MigrateImportedPhoneNumberTypes',
            $attributes[0]->newInstance()->identifier,
        );
        $subject = $this->get(MigrateImportedPhoneNumberTypesUpgradeWizard::class);
        $this->assertInstanceOf(MigrateImportedPhoneNumberTypesUpgradeWizard::class, $subject);
        $this->assertSame([DatabaseUpdatedPrerequisite::class], $subject->getPrerequisites());
    }

    #[Test]
    public function migratesImportedPhoneNumbersIdempotently(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/imported-phone-number-types.csv');
        $subject = $this->get(MigrateImportedPhoneNumberTypesUpgradeWizard::class);

        $this->assertTrue($subject->updateNecessary());
        $this->assertTrue($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/imported-phone-number-types.csv');
        $this->assertFalse($subject->updateNecessary());

        $this->assertTrue($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/imported-phone-number-types.csv');
    }

    #[Test]
    public function preservesLegacyTypeValuesWhenTheyAreSelectable(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['academic_persons']['types']['phoneNumberTypes'] =
            'business=Business,phone=Phone,fax=Fax';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/imported-phone-number-types.csv');
        $subject = $this->get(MigrateImportedPhoneNumberTypesUpgradeWizard::class);

        $this->assertTrue($subject->executeUpdate());
        $connection = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_phone_number');
        $telephone = $connection->select(
            ['type', 'import_identifier'],
            'tx_academicpersons_domain_model_phone_number',
            ['uid' => 1],
        )->fetchAssociative();
        $fax = $connection->select(
            ['type', 'import_identifier'],
            'tx_academicpersons_domain_model_phone_number',
            ['uid' => 2],
        )->fetchAssociative();

        $this->assertSame(
            ['type' => 'phone', 'import_identifier' => 'telephone:fe_users:1'],
            $telephone,
        );
        $this->assertSame(
            ['type' => 'fax', 'import_identifier' => 'fax:fe_users:2'],
            $fax,
        );
    }
}
