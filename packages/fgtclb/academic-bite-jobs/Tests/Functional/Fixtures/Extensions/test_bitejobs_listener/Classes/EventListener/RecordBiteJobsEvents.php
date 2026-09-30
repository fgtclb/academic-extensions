<?php

declare(strict_types=1);

namespace TESTS\TestBitejobsListener\EventListener;

use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent;
use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent;

/**
 * Records every event of the B-ITE job service, so a test asserts what a listener is handed.
 * Registered after the listeners that change something, so the recorded event carries their
 * changes. A test empties the record in its `setUp()`.
 */
final class RecordBiteJobsEvents
{
    /**
     * @var list<ModifyBiteJobPostingsRequestEvent|ModifyBiteJobPostingsEvent>
     */
    public static array $events = [];

    public function recordRequest(ModifyBiteJobPostingsRequestEvent $event): void
    {
        self::$events[] = $event;
    }

    public function recordResult(ModifyBiteJobPostingsEvent $event): void
    {
        self::$events[] = $event;
    }
}
