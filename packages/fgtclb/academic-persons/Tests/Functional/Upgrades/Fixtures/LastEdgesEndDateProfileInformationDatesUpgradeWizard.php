<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades\Fixtures;

use FGTCLB\AcademicBase\Date\DateCompletion;
use FGTCLB\AcademicBase\Date\DateCompletionEdge;
use FGTCLB\AcademicPersons\Upgrades\AbstractMigrateProfileInformationDatesUpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * The shape a project that reads a period as "from the beginning of the start
 * year to the end of the end year" configures: the start columns keep the
 * inherited first/first rule, the end column completes to the last day of the
 * last month.
 */
#[UpgradeWizard('academicPersonsTest_migrateProfileInformationDatesLastEdges')]
final readonly class LastEdgesEndDateProfileInformationDatesUpgradeWizard extends AbstractMigrateProfileInformationDatesUpgradeWizard
{
    public function __construct(ConnectionPool $connectionPool)
    {
        parent::__construct(
            $connectionPool,
            new DateCompletion(),
            new DateCompletion(DateCompletionEdge::LAST, DateCompletionEdge::LAST),
        );
    }
}
