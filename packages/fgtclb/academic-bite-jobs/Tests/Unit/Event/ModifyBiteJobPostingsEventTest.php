<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Unit\Event;

use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The postings a listener hands back are sliced to the limit and rendered by the templates
 * as they are, so the setter is the one place that can refuse what the templates cannot
 * render.
 */
final class ModifyBiteJobPostingsEventTest extends UnitTestCase
{
    private function subject(): ModifyBiteJobPostingsEvent
    {
        return new ModifyBiteJobPostingsEvent(
            [['id' => 1, 'title' => 'First']],
            ['jobPostings' => [['id' => 1, 'title' => 'First']]],
            ['jobListingKey' => 'test-key'],
            new ServerRequest('https://www.acme.com/'),
        );
    }

    #[Test]
    public function setJobPostingsReplacesThePostings(): void
    {
        $subject = $this->subject();
        $jobs = [['id' => 2, 'title' => 'Second'], ['id' => 3, 'title' => 'Third', 'relationName' => 'Research']];

        $subject->setJobPostings($jobs);

        $this->assertSame($jobs, $subject->getJobPostings());
    }

    #[Test]
    public function setJobPostingsAcceptsAnEmptyList(): void
    {
        $subject = $this->subject();

        $subject->setJobPostings([]);

        $this->assertSame([], $subject->getJobPostings());
    }

    /**
     * @return \Generator<string, array{array<mixed>, int}>
     */
    public static function invalidJobsDataProvider(): \Generator
    {
        yield 'a map instead of a list' => [['first' => ['id' => 1]], 1790774355];
        yield 'a list with a gap' => [[0 => ['id' => 1], 2 => ['id' => 2]], 1790774355];
        yield 'a title among the postings' => [[['id' => 1], 'Second'], 1790774356];
        yield 'an object among the postings' => [[['id' => 1], new \stdClass()], 1790774356];
    }

    /**
     * @param array<mixed> $jobs
     */
    #[DataProvider('invalidJobsDataProvider')]
    #[Test]
    public function setJobPostingsRejectsAnythingButAListOfPostings(array $jobs, int $expectedCode): void
    {
        $subject = $this->subject();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedCode);

        $subject->setJobPostings($jobs);
    }

    #[Test]
    public function aRejectedListLeavesThePostingsUnchanged(): void
    {
        $subject = $this->subject();
        $jobs = $subject->getJobPostings();

        try {
            // @phpstan-ignore argument.type
            $subject->setJobPostings(['Second']);
        } catch (\InvalidArgumentException) {
        }

        $this->assertSame($jobs, $subject->getJobPostings());
    }
}
