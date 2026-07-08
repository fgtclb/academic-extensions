<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Domain\Model\Project;
use FGTCLB\AcademicProjects\Domain\Repository\ProjectRepository;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
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
}
