<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Services;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class BiteJobsService
{
    /**
     * @var array<string|array<string, mixed>, mixed>|null $responseBody
     * @todo Response state on a service class ? A really really bad idea.
     */
    protected $responseBody;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * custom.zuordnung is a custom Field set by select type (@05.09.2023) custom fields are:
     * 01: Berufungsverfahren
     * 02: Wissenschaftliches Personal
     * 03: Nicht-wissenschaftliches Personal
     * 04: Ausbildungsstellen
     * @return string[]
     */
    public function fetchBiteJobs(?ServerRequestInterface $request = null): array
    {
        $flexformTool = GeneralUtility::makeInstance(FlexFormService::class);

        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer($request ?? $GLOBALS['TYPO3_REQUEST'] ?? new ServerRequest())?->data ?? [];
        $settings = $flexformTool->convertFlexFormContentToArray((string)($contentElementData['pi_flexform'] ?? ''));

        $jobsSettings = $settings['settings']['jobs'];

        $filter = [];

        if ($jobsSettings['custom']['zuordnung'] !== 'all') {
            $filter = [
                'custom.zuordnung' => [
                    'in' => [$jobsSettings['custom']['zuordnung']],
                ],
            ];
        }

        $jobs = [];
        $additionalOptions = json_encode([
            'key' => $jobsSettings['jobListingKey'],
            'channel' => 0,
            'locale' => 'de',
            'page' => [
                'offset' => 0,
            ],
            'filter' => $filter,
            'sort' => [
                'order' => $jobsSettings['sortingDirection'],
                'by' => $jobsSettings['sortBy'],
            ],
        ]);

        $searchUrl = 'https://jobs.b-ite.com/api/v1/postings/search';

        try {
            $response = $this->requestFactory->request($searchUrl, 'POST', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $additionalOptions,
            ]);

            $this->responseBody = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Error while fetching jobs from Bite API: %s',
                $e->getMessage()
            ));
        }
        if (!empty($this->responseBody['jobPostings'])) {
            // We need to map the custom fields to the jobs so we have the labels instead of the values in the frontend
            $jobs = $this->mapFieldsToJobs($this->responseBody['jobPostings'], 'umfang');
            $jobs = $this->groupByRelations($jobs);
        }

        // Set Job Limit if setting is not empty
        if (!empty($jobsSettings['limit'])) {
            $jobs = array_slice($jobs, 0, (int)$jobsSettings['limit']);
        }

        return $jobs;
    }

    /**
     * @return string[]
     */
    public function findCustomJobRelations(): array
    {
        $fields = [];

        if (isset($this->responseBody['fields']['custom.zuordnung']['options'])) {
            foreach ($this->responseBody['fields']['custom.zuordnung']['options'] as $value) {
                $fields[$value['value']] = $value['label'];
            }
        }
        return $fields;
    }

    /**
     * As the Bite API has custom fields, some of thoses have labels and some just values.
     * To be able to map the values to the labels, we need to find the labels first in the Fields.
     * @return string[]
     */
    public function findCustomBiteFieldLabelsFromOptions(string $item): array
    {
        $fields = [];

        if (isset($this->responseBody['fields']['custom.' . $item]['options'])) {
            foreach ($this->responseBody['fields']['custom.' . $item]['options'] as $key => $value) {
                $fields[$value['value']] = $value['label'];
            }
        }
        return $fields;
    }

    /**
     * @param array<string, mixed> $jobs
     * @return string[]
     */
    public function groupByRelations(array $jobs): array
    {
        $grouped = [];
        $biteRelations = $this->findCustomJobRelations();

        foreach ($jobs as $job) {
            foreach ($biteRelations as $relationKey => $relationValue) {
                if ($relationKey === $job['custom']['zuordnung'][0]) {
                    $job['relationName'] = $relationValue;
                    $grouped[] = $job;
                }
            }
        }

        return $grouped;
    }

    /**
     * @param array<string, mixed> $jobs
     * @return string[]
     */
    public function mapFieldsToJobs(array $jobs, string $customFieldName): array
    {
        $grouped = [];
        $field = $this->findCustomBiteFieldLabelsFromOptions($customFieldName);

        foreach ($jobs as $job) {
            $job[$customFieldName] = $field;
            $grouped[] = $job;
        }

        return $grouped;
    }

    private function getCurrentContentObjectRenderer(ServerRequestInterface $request): ?ContentObjectRenderer
    {
        return $request->getAttribute('currentContentObject');
    }
}
