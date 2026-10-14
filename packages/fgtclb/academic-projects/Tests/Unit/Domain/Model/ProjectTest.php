<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\Domain\Model;

use FGTCLB\AcademicProjects\Domain\Model\Project;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProjectTest extends UnitTestCase
{
    /**
     * A project that was never saved has neither uid nor pid, which Extbase keeps as
     * `null`. Reading them must answer that instead of failing with a `TypeError`.
     */
    #[Test]
    public function anUnsavedProjectHasNeitherUidNorPid(): void
    {
        $project = new Project();

        $this->assertNull($project->getUid());
        $this->assertNull($project->getPid());
    }
}
