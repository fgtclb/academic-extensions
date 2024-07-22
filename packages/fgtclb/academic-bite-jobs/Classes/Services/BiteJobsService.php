<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Services;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class BiteJobsService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array<string|array<string, mixed>, mixed>|null $responseBody
     */
    protected $responseBody;

    /**
     * @var array<string, string>
     */
    protected array $headers;

    protected string $url;

    protected RequestFactory $requestFactory;

    public function __construct(RequestFactory $requestFactory) {
        $this->requestFactory = $requestFactory ?? GeneralUtility::makeInstance(RequestFactory::class);

        $this->url = 'https://jobs.b-ite.com/api/v1/';
        $this->headers = ['Content-Type' => 'application/json'];
    }

    /**
     * custom.zuordnung is a custom Field set by select type (@05.09.2023) custom fields are:
     * 01: Berufungsverfahren
     * 02: Wissenschaftliches Personal
     * 03: Nicht-wissenschaftliches Personal
     * 04: Ausbildungsstellen
     * @return string[]
     */
    public function fetchBiteJobs(): array
    {
        $flexformTool = GeneralUtility::makeInstance(FlexFormService::class);
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);

        /** @var ContentObjectRenderer $cObj */
        $cObj = $configurationManager->getContentObject();
        $settings = $flexformTool->convertFlexFormContentToArray($cObj->data['pi_flexform']);

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

        $searchUrl = $this->url . 'postings/search';

        try {
            $response = $this->requestFactory->request($searchUrl, 'POST', [
                'headers' => $this->headers,
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
}
