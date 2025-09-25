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
            $jobs = $this->responseBody['jobPostings'];
        }

        if (!empty($jobsSettings['limit'])) {
            $jobs = array_slice($jobs, 0, (int)$jobsSettings['limit']);
        }

        return $jobs;
    }

    private function getCurrentContentObjectRenderer(ServerRequestInterface $request): ?ContentObjectRenderer
    {
        return $request->getAttribute('currentContentObject');
    }
}
