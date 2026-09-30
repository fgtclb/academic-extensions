<?php

declare(strict_types=1);

namespace TESTS\TestBitejobsListener\EventListener;

use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent;
use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Records every event of the B-ITE job service, and the plugin view event of the job list, so
 * a test asserts what a listener is handed. Registered after the listeners that change
 * something, so the recorded event carries their changes. A test empties the record in its
 * `setUp()`.
 */
final class RecordBiteJobsEvents
{
    /**
     * @var list<ModifyBiteJobPostingsRequestEvent|ModifyBiteJobPostingsEvent|ModifyPluginViewEvent>
     */
    public static array $events = [];

    #[AsEventListener(identifier: 'test-bitejobs-listener/record-request', after: 'test-bitejobs-listener/change-request')]
    public function recordRequest(ModifyBiteJobPostingsRequestEvent $event): void
    {
        self::$events[] = $event;
    }

    #[AsEventListener(identifier: 'test-bitejobs-listener/record-result', after: 'test-bitejobs-listener/change-postings')]
    public function recordResult(ModifyBiteJobPostingsEvent $event): void
    {
        self::$events[] = $event;
    }

    #[AsEventListener(identifier: 'test-bitejobs-listener/record-view')]
    public function recordView(ModifyPluginViewEvent $event): void
    {
        self::$events[] = $event;
    }
}
