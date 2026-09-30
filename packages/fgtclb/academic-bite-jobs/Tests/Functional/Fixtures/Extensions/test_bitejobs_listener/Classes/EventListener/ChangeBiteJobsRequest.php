<?php

declare(strict_types=1);

namespace TESTS\TestBitejobsListener\EventListener;

use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Changes the request to B-ITE the way a project does it: a filter on a custom field, as
 * the project that forked 2.0.2 sends it, and another locale.
 *
 * Both are driven by fields a test adds to the plugin FlexForm, `settings.jobs.testZuordnung`
 * and `settings.jobs.testLocale`, which the event hands over with the plugin settings.
 * `settings.jobs.testBreakPayload` makes the payload impossible to encode as JSON. The
 * listener stays inert for every content element without them.
 */
final class ChangeBiteJobsRequest
{
    #[AsEventListener(identifier: 'test-bitejobs-listener/change-request')]
    public function __invoke(ModifyBiteJobPostingsRequestEvent $event): void
    {
        $settings = $event->getSettings();
        $payload = $event->getPayload();
        $zuordnung = (string)($settings['testZuordnung'] ?? '');
        if ($zuordnung !== '') {
            $payload['filter'] = ['custom.zuordnung' => ['in' => [$zuordnung]]];
        }
        $locale = (string)($settings['testLocale'] ?? '');
        if ($locale !== '') {
            $payload['locale'] = $locale;
        }
        if (($settings['testBreakPayload'] ?? '') === '1') {
            // Not valid UTF-8, so the payload cannot be encoded as JSON.
            $payload['locale'] = "\xB1";
        }
        $event->setPayload($payload);
    }
}
