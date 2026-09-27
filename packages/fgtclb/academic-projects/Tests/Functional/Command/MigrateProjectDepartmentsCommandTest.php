<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Command;

use FGTCLB\AcademicPrograms\Enumeration\PageTypes as ProgramPageTypes;
use FGTCLB\AcademicProjects\Command\MigrateProjectDepartmentsCommand;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * With `academic_programs` installed, a `department` category is a projects department
 * only when every program or project page it is assigned to is a project page.
 *
 * Categories of the fixture, all of type `department` unless noted:
 *
 * - Physics (1) on a project page, with a translation (101) and a workspace version (201)
 * - Engineering (2) on a program page, with a translation (102)
 * - Mathematics (3) on a project page and a program page
 * - Chemistry (4) on no page
 * - Biology (5) on a hidden project page
 * - Medicine (6) on a deleted project page only
 * - History (7) on a project page and a standard page
 * - Photonics (8), a competence field, on a project page
 * - Geology (9), deleted, on a project page
 */
final class MigrateProjectDepartmentsCommandTest extends AbstractAcademicProjectsTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'fgtclb/academic-programs';
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/MigrateProjectDepartmentsCommand/departments.csv');
    }

    /**
     * The command cannot use the page type class of `academic_programs`, which is not a
     * dependency, so it repeats the value. This test is the one place that loads both.
     */
    #[Test]
    public function theProgramDoktypeIsTheOneOfAcademicPrograms(): void
    {
        $this->assertSame(
            ProgramPageTypes::TYPE_ACADEMIC_PROGRAM,
            (new \ReflectionClassConstant(MigrateProjectDepartmentsCommand::class, 'PROGRAM_DOKTYPE'))->getValue(),
        );
    }

    #[Test]
    public function departmentsOfProjectPagesMoveWithTheirTranslationsAndVersions(): void
    {
        $this->runCommand();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MigrateProjectDepartmentsCommand/migratedWithPrograms.csv');
    }

    #[Test]
    public function theNumberOfMovedCategoriesIsReported(): void
    {
        $this->assertStringContainsString('3 categories moved to "project_department".', $this->runCommand());
    }

    /**
     * A category on program and project pages, or on none of them, is a decision for the
     * integrator. Deleted pages do not count, which is why Medicine is on none.
     */
    #[Test]
    public function ambiguousCategoriesAreListedInUidOrder(): void
    {
        $this->assertSame(
            [
                ['3', 'Mathematics', 'program and project pages'],
                ['4', 'Chemistry', 'no program or project page'],
                ['6', 'Medicine', 'no program or project page'],
            ],
            $this->listedCategories($this->runCommand()),
        );
    }

    #[Test]
    public function aSecondRunMovesNothingAndListsTheAmbiguousCategoriesAgain(): void
    {
        $this->runCommand();

        $output = $this->runCommand();

        $this->assertStringContainsString('No category left to move.', $output);
        $this->assertSame(['3', '4', '6'], array_column($this->listedCategories($output), 0));
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MigrateProjectDepartmentsCommand/migratedWithPrograms.csv');
    }

    /**
     * Runs the command and returns its output. An ambiguous category is a decision for the
     * integrator, not a failure, so the status is always success.
     */
    private function runCommand(): string
    {
        $command = $this->get(MigrateProjectDepartmentsCommand::class);
        $this->assertInstanceOf(MigrateProjectDepartmentsCommand::class, $command);

        $tester = new CommandTester($command);
        $this->assertSame(Command::SUCCESS, $tester->execute([]));

        return $tester->getDisplay();
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function listedCategories(string $output): array
    {
        preg_match_all('/^\s*(\d+)\s{2,}(.+?)\s{2,}(.+?)\s*$/m', $output, $matches, PREG_SET_ORDER);

        return array_map(static fn(array $match): array => [$match[1], $match[2], $match[3]], $matches);
    }
}
