<?php

declare(strict_types=1);

namespace TESTS\TestJobsFormPrefill\EventListener;

use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Domain\Repository\JobRepository;
use FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Assigns the stored job with uid 1 as the `job` the new job form is bound to, which is
 * how an integrator offers an existing record for editing: `JobController::newAction()`
 * assigns no form object of its own, and this event is the documented place to add one.
 *
 * The uid is hard coded because the fixture data set of the test that loads this
 * extension is, too. `findByIdentifier()` ignores the storage page, so the job does not
 * have to live below the plugin's own one.
 */
final class OfferStoredJobForEditing
{
    public function __construct(
        private readonly JobRepository $jobRepository,
    ) {}

    #[AsEventListener(identifier: 'test-jobs-form-prefill/offer-stored-job-for-editing')]
    public function __invoke(ModifyJobControllerNewActionViewEvent $event): void
    {
        $job = $this->jobRepository->findByIdentifier(1);
        if (!$job instanceof Job) {
            return;
        }
        $event->getView()->assign('job', $job);
    }
}
