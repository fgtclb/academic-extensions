<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dispatched in {@see BiteJobsService::fetchBiteJobs()}, which the job list asks for its
 * postings, after the response of the B-ITE API is decoded. A listener removes, changes or
 * adds postings, or adds a value to each of them, for example a key to group the list by.
 *
 * It is dispatched on every call, after a failed request too, then with no postings and no
 * response data. The configured limit of the job list is applied to the postings the
 * listeners hand back, so a listener sees every posting of the response.
 *
 * @api
 */
final class ModifyBiteJobPostingsEvent
{
    /**
     * @param list<array<string, mixed>> $jobPostings
     * @param array<mixed> $responseData
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private array $jobPostings,
        private readonly array $responseData,
        private readonly array $settings,
        private readonly ServerRequestInterface $request,
        private readonly ?PluginControllerActionContextInterface $pluginControllerActionContext = null,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function getJobPostings(): array
    {
        return $this->jobPostings;
    }

    /**
     * @param list<array<string, mixed>> $jobPostings
     * @throws \InvalidArgumentException when the value is not a list of postings
     */
    public function setJobPostings(array $jobPostings): void
    {
        if (!array_is_list($jobPostings)) {
            throw new \InvalidArgumentException(
                'The job postings must be a list, keyed from 0 without gaps.',
                1790774355,
            );
        }
        foreach ($jobPostings as $jobPosting) {
            if (!is_array($jobPosting)) {
                throw new \InvalidArgumentException(
                    sprintf('Every job posting must be an array, %s given.', get_debug_type($jobPosting)),
                    1790774356,
                );
            }
        }
        $this->jobPostings = $jobPostings;
    }

    /**
     * The decoded response of the B-ITE API, or an empty array when the request failed or
     * the response was not JSON.
     *
     * @return array<mixed>
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * The values stored below `settings.jobs` in the FlexForm of the content element, which
     * the request was built from: not merged with TypoScript, and not normalised, so a
     * content element saved with 2.0 still carries its old view value here. The settings the
     * plugin works with, TypoScript included, are those of the plugin action context.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * The request of the page being rendered.
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * The context of the plugin action that asked for the postings, with the settings of the
     * plugin, or `null` when the service was called outside of a plugin action.
     */
    public function getPluginControllerActionContext(): ?PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
