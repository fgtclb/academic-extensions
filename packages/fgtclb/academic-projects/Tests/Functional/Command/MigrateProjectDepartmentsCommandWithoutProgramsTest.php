<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Command;

use FGTCLB\AcademicProjects\Command\MigrateProjectDepartmentsCommand;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Without `academic_programs` every `department` category is a projects department,
 * wherever it is assigned. Deleted categories stay as they are. The fixture is the one of
 * {@see MigrateProjectDepartmentsCommandTest}.
 */
final class MigrateProjectDepartmentsCommandWithoutProgramsTest extends AbstractAcademicProjectsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/MigrateProjectDepartmentsCommand/departments.csv');
    }

    #[Test]
    public function everyDepartmentCategoryMoves(): void
    {
        $command = $this->get(MigrateProjectDepartmentsCommand::class);
        $this->assertInstanceOf(MigrateProjectDepartmentsCommand::class, $command);
        $tester = new CommandTester($command);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MigrateProjectDepartmentsCommand/migratedWithoutPrograms.csv');
        $this->assertStringContainsString('7 categories moved to "project_department".', $tester->getDisplay());
        $this->assertStringNotContainsString('Mathematics', $tester->getDisplay());
    }
}
