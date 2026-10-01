<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Domain\Model\Project;
use FGTCLB\AcademicProjects\Domain\Repository\ProjectRepository;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class ProjectRepositoryTest extends AbstractAcademicProjectsTestCase
{
    private function getProjectRepository(): ProjectRepository
    {
        return $this->get(ProjectRepository::class);
    }

    private function createDemand(bool $showHiddenRecords): ProjectDemand
    {
        $demand = new ProjectDemand();
        $demand->setShowHiddenRecords($showHiddenRecords);
        return $demand;
    }

    /**
     * @param QueryResultInterface<int, Project> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $project) {
            $uids[] = (int)$project->getUid();
        }
        sort($uids);
        return $uids;
    }

    #[Test]
    public function findByDemandExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projects.csv');
        $result = $this->getProjectRepository()->findByDemand($this->createDemand(false));
        $this->assertSame([1, 3], $this->resultUids($result));
    }

    #[Test]
    public function findByDemandIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projects.csv');
        $result = $this->getProjectRepository()->findByDemand($this->createDemand(true));
        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }

    /**
     * The default state. Nothing is added to the query, so every project page is returned
     * whatever its end date - the baseline the two filtered states below are read against.
     */
    #[Test]
    public function findByDemandReturnsEveryProjectRegardlessOfItsEndDateByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projectsWithEndDates.csv');
        $result = $this->getProjectRepository()->findByDemand(new ProjectDemand());
        $this->assertSame([10, 11, 12, 13], $this->resultUids($result));
    }

    /**
     * "Active" is two conditions, not one: an end date of `0` means the project has no
     * planned end and stays in the list forever, and anything still in the future is
     * running. Dropping the `equals(…, 0)` half is the plausible simplification, and it
     * would silently hide every open ended project.
     */
    #[Test]
    public function theActiveStateSelectsOpenEndedAndStillRunningProjects(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projectsWithEndDates.csv');
        $result = $this->getProjectRepository()->findByDemand($this->createDemandForActiveState('active'));
        $this->assertSame([10, 11], $this->resultUids($result));
    }

    /**
     * The counterpart is deliberately not the complement: it requires an end date that is
     * both set and passed, so an open ended project is in neither list.
     */
    #[Test]
    public function theCompletedStateSelectsProjectsWhoseEndDateHasPassed(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projectsWithEndDates.csv');
        $result = $this->getProjectRepository()->findByDemand($this->createDemandForActiveState('completed'));
        $this->assertSame([12], $this->resultUids($result));
    }

    /**
     * `tx_academicprojects_end_date` is `DEFAULT NULL` in `ext_tables.sql` while the TCA
     * field is not `nullable`, so FormEngine writes `0` and a row that never went through
     * it keeps `NULL` - a page created before the extension was installed, or by an import.
     * Such a project is in neither state: `NULL = 0` and `NULL > <now>` are both unknown in
     * SQL, so it disappears from a list that filters at all while being visible in the
     * unfiltered one. Uid 13 of the fixture is that row. ACE-433 is the bugfix that decides
     * which list it belongs to.
     */
    #[Test]
    public function aProjectWithoutAnyEndDateValueIsNeitherActiveNorCompleted(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projectsWithEndDates.csv');

        $active = $this->getProjectRepository()->findByDemand($this->createDemandForActiveState('active'));
        $completed = $this->getProjectRepository()->findByDemand($this->createDemandForActiveState('completed'));

        $this->assertNotContains(13, $this->resultUids($active));
        $this->assertNotContains(13, $this->resultUids($completed));
    }

    /**
     * A template shows the state of a project, `Project::getActiveState()`, and a visitor
     * filters by the query above. Both implement one rule, so every project a filter lists
     * has to be in the state of that filter. The projects come through the Extbase date
     * mapping here: an end date of `0` has to arrive as no end date, or the open ended
     * project would read as completed.
     *
     * @param array<int, string> $expected
     */
    #[Test]
    #[DataProvider('filteredStates')]
    public function everyProjectAFilterListsIsInTheStateOfThatFilter(string $activeState, array $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projectsWithEndDates.csv');

        $states = [];
        foreach ($this->getProjectRepository()->findByDemand($this->createDemandForActiveState($activeState)) as $project) {
            $states[(int)$project->getUid()] = $project->getActiveState();
        }
        ksort($states);

        $this->assertSame($expected, $states);
    }

    /**
     * @return \Generator<string, array{0: string, 1: array<int, string>}>
     */
    public static function filteredStates(): \Generator
    {
        yield 'active' => ['active', [10 => 'active', 11 => 'active']];
        yield 'completed' => ['completed', [12 => 'completed']];
    }

    /**
     * The one place where the two disagree today. A project whose end date column holds
     * `NULL` has no end date for the model, so it is active, while the "Active" filter
     * misses it, see `aProjectWithoutAnyEndDateValueIsNeitherActiveNorCompleted()` and the
     * "active" case above. ACE-433 puts it into that filter and turns those around.
     */
    #[Test]
    public function aProjectWithoutAnyEndDateValueIsActive(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projectsWithEndDates.csv');

        $states = [];
        foreach ($this->getProjectRepository()->findByDemand(new ProjectDemand()) as $project) {
            $states[(int)$project->getUid()] = $project->getActiveState();
        }

        $this->assertSame('active', $states[13] ?? null);
    }

    /**
     * With `showSelected` - which `DemandFactory` derives from the single view CType - the
     * plugin's `pages` field names the project records themselves, matched on `uid`
     * instead of `pid`.
     *
     * Its counterpart, the storage page branch, is deliberately not asserted here: it
     * switches `setRespectStoragePage(true)` back on, and that restriction is fed from the
     * Extbase framework configuration rather than from the demand. In a frontend request
     * `FrontendConfigurationManager::overrideStoragePidIfStartingPointIsSet()` fills it
     * from the same `pages` field (which is what adds `recursive` support), while a
     * repository called outside a request gets the default `storagePid = 0` and the two
     * restrictions intersect to nothing. That branch is therefore only meaningful in a
     * plugin rendering test.
     */
    #[Test]
    public function showSelectedTurnsThePageSelectionIntoARecordSelection(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projectsWithEndDates.csv');
        $demand = new ProjectDemand();
        $demand->setPages([11, 13]);
        $demand->setShowSelected(true);
        $result = $this->getProjectRepository()->findByDemand($demand);
        $this->assertSame([11, 13], $this->resultUids($result));
    }

    /**
     * The `uid` tiebreaker of `findByDemand()` (ACE-491): three projects share one title,
     * so the default `title asc` ordering leaves their relative order undefined - and the
     * DBMS resolved it, arbitrarily on PostgreSQL. The fixture's `sorting` values are
     * deliberately reversed against uid order, so a `sorting`-based accident cannot
     * produce the expected list. SQLite cannot make the tie itself fail - uid order is
     * its natural order - so the assertion pins the contract for the DBMS where a tie is
     * otherwise resolved arbitrarily.
     */
    #[Test]
    public function projectsEqualInTheDemandedOrderingFallBackToUidOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectRepository/projectsWithEqualTitles.csv');

        $uids = [];
        foreach ($this->getProjectRepository()->findByDemand(new ProjectDemand()) as $project) {
            $uids[] = (int)$project->getUid();
        }

        $this->assertSame([10, 11, 12], $uids);
    }

    private function createDemandForActiveState(string $activeState): ProjectDemand
    {
        $demand = new ProjectDemand();
        $demand->setActiveState($activeState);
        return $demand;
    }
}
