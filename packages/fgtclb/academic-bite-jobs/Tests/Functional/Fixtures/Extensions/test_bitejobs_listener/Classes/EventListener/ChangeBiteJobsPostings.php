<?php

declare(strict_types=1);

namespace TESTS\TestBitejobsListener\EventListener;

use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Changes the postings the way a project does it, driven by settings so that one fixture
 * extension serves every scenario and stays inert for every other content element:
 *
 * - `settings.jobs.testRemoveTitle` in the plugin FlexForm removes the posting of that title.
 * - `settings.jobs.testFallbackTitle` in the plugin FlexForm adds a posting of that title
 *   when no posting is left, which is how a test sees that the event is dispatched after a
 *   failed request too.
 * - `settings.testRelationNames` in the TypoScript of the plugin maps the department of
 *   every posting to a relation name the list can be grouped by. It is read from the plugin
 *   action context, which carries the TypoScript settings the plugin settings of the event
 *   do not.
 */
final class ChangeBiteJobsPostings
{
    #[AsEventListener(identifier: 'test-bitejobs-listener/change-postings')]
    public function __invoke(ModifyBiteJobPostingsEvent $event): void
    {
        $settings = $event->getSettings();
        $jobs = $event->getJobPostings();

        $removeTitle = (string)($settings['testRemoveTitle'] ?? '');
        if ($removeTitle !== '') {
            $jobs = array_values(array_filter(
                $jobs,
                static fn(array $job): bool => ($job['title'] ?? null) !== $removeTitle,
            ));
        }

        $fallbackTitle = (string)($settings['testFallbackTitle'] ?? '');
        if ($fallbackTitle !== '' && $jobs === []) {
            $jobs[] = ['id' => 0, 'title' => $fallbackTitle];
        }

        $relationNames = $event->getPluginControllerActionContext()?->getSettings()['testRelationNames'] ?? null;
        if (is_array($relationNames)) {
            foreach ($jobs as $index => $job) {
                $jobs[$index]['relationName'] = $relationNames[$job['department'] ?? ''] ?? '';
            }
        }

        $event->setJobPostings($jobs);
    }
}
