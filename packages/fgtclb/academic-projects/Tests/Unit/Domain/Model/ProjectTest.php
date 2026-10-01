<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\Domain\Model;

use FGTCLB\AcademicProjects\Domain\Model\Project;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * A template reads `{project.activeState}`, so the state has to come from the model
 * itself, from the end date Extbase mapped onto it and the current time. It is the
 * string value of the state: Fluid cannot print an enum.
 */
final class ProjectTest extends UnitTestCase
{
    #[Test]
    #[DataProvider('endDates')]
    public function theActiveStateFollowsTheEndDate(?\DateTime $endDate, string $expected): void
    {
        $project = new Project();
        $project->_setProperty('endDate', $endDate);

        $this->assertSame($expected, $project->getActiveState());
    }

    /**
     * @return \Generator<string, array{0: ?\DateTime, 1: string}>
     */
    public static function endDates(): \Generator
    {
        yield 'no end date' => [null, 'active'];
        yield 'ending tomorrow' => [new \DateTime('+1 day'), 'active'];
        yield 'ended yesterday' => [new \DateTime('-1 day'), 'completed'];
    }
}
