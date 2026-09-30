<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Services;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent;
use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class BiteJobsService
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchBiteJobs(
        ?ServerRequestInterface $request = null,
        ?PluginControllerActionContextInterface $pluginControllerActionContext = null,
    ): array {
        $request ??= $GLOBALS['TYPO3_REQUEST'] ?? new ServerRequest();
        $flexformTool = GeneralUtility::makeInstance(FlexFormService::class);

        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer($request)?->data ?? [];
        $settings = $flexformTool->convertFlexFormContentToArray((string)($contentElementData['pi_flexform'] ?? ''));

        // A content element without a stored FlexForm, created by an import for example,
        // has no settings at all. It sends the request with empty values, as it always did,
        // instead of failing on the missing keys.
        $jobsSettings = is_array($settings['settings']['jobs'] ?? null) ? $settings['settings']['jobs'] : [];

        $requestEvent = new ModifyBiteJobPostingsRequestEvent(
            [
                'key' => $jobsSettings['jobListingKey'] ?? null,
                'channel' => 0,
                'locale' => 'de',
                'page' => [
                    'offset' => 0,
                ],
                'filter' => [],
                'sort' => [
                    'order' => $jobsSettings['sortingDirection'] ?? null,
                    'by' => $jobsSettings['sortBy'] ?? null,
                ],
            ],
            $jobsSettings,
            $request,
            $pluginControllerActionContext,
        );
        $this->eventDispatcher->dispatch($requestEvent);

        $searchUrl = 'https://jobs.b-ite.com/api/v1/postings/search';

        $responseData = [];
        try {
            $response = $this->requestFactory->request($searchUrl, 'POST', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($requestEvent->getPayload(), JSON_THROW_ON_ERROR),
            ]);

            $decoded = json_decode($response->getBody()->getContents(), true);
            if (is_array($decoded)) {
                $responseData = $decoded;
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Error while fetching jobs from Bite API: %s',
                $e->getMessage()
            ));
        }

        $jobPostings = [];
        if (is_array($responseData['jobPostings'] ?? null)) {
            $jobPostings = array_values(array_filter($responseData['jobPostings'], is_array(...)));
        }

        $resultEvent = new ModifyBiteJobPostingsEvent(
            $jobPostings,
            $responseData,
            $jobsSettings,
            $request,
            $pluginControllerActionContext,
        );
        $this->eventDispatcher->dispatch($resultEvent);
        $jobPostings = $resultEvent->getJobPostings();

        if (!empty($jobsSettings['limit'])) {
            $jobPostings = array_slice($jobPostings, 0, (int)$jobsSettings['limit']);
        }

        return $jobPostings;
    }

    private function getCurrentContentObjectRenderer(ServerRequestInterface $request): ?ContentObjectRenderer
    {
        return $request->getAttribute('currentContentObject');
    }
}
