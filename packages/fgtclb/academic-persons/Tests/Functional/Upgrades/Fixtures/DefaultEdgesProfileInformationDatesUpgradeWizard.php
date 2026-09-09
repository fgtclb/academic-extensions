<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades\Fixtures;

use FGTCLB\AcademicPersons\Upgrades\AbstractMigrateProfileInformationDatesUpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * A project subclass that keeps the inherited defaults: every year is completed
 * to the first day of the first month, the end year included.
 */
#[UpgradeWizard('academicPersonsTest_migrateProfileInformationDatesDefaultEdges')]
final readonly class DefaultEdgesProfileInformationDatesUpgradeWizard extends AbstractMigrateProfileInformationDatesUpgradeWizard
{
    public function __construct(ConnectionPool $connectionPool)
    {
        parent::__construct($connectionPool);
    }
}
