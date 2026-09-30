<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Answers the requests of `BiteJobsService` from memory and builds the request the service
 * reads its plugin settings from, for the tests that call the service directly.
 *
 * - **No test performs an outgoing request.** A case installs a Guzzle handler in
 *   `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']`, which `GuzzleClientFactory` uses
 *   verbatim instead of `HandlerStack::create()`, and reads it for every request. The handler
 *   answers from memory, and `backupGlobals` restores the setting after each test. Stubbing
 *   at handler level is the same technique the `test_bitejobs_stub` fixture extension uses,
 *   and for the same reason: `RequestFactory` builds its client through
 *   `GuzzleClientFactory` rather than taking one, and `BiteJobsService` is `final`, so
 *   neither can be replaced.
 * - The service reads its settings from the FlexForm of the content element it is rendered
 *   in, reached through the `currentContentObject` request attribute.
 *   `flexFormWithJobSettings()` builds that XML in the shape FormEngine stores it - see the
 *   `pi_flexform` column of
 *   `Tests/Functional/Plugins/Fixtures/AcademicBiteJobsListPlugin/biteJobsListPage.csv`.
 */
trait BiteJobsApiStubTrait
{
    /**
     * The request the stub handler was asked to answer last, for the cases asserting the
     * payload.
     */
    private ?RequestInterface $handledRequest = null;

    /**
     * The request the stub handler answered, asserted to exist - a case reaching this without
     * an outgoing request would silently assert nothing otherwise.
     */
    private function handledRequest(): RequestInterface
    {
        $handledRequest = $this->handledRequest;
        $this->assertNotNull($handledRequest);

        return $handledRequest;
    }

    /**
     * @return array<mixed>
     */
    private function handledPayload(): array
    {
        $payload = json_decode((string)$this->handledRequest()->getBody(), true);
        $this->assertIsArray($payload);

        return $payload;
    }

    /**
     * @param callable(RequestInterface, array<string, mixed>): PromiseInterface $handler
     */
    private function installHandler(callable $handler): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = HandlerStack::create($handler);
    }

    /**
     * @return callable(RequestInterface, array<string, mixed>): PromiseInterface
     */
    private function respondWith(int $statusCode, string $body): callable
    {
        return function (RequestInterface $request) use ($statusCode, $body): PromiseInterface {
            $this->handledRequest = $request;

            return Create::promiseFor(new Response($statusCode, ['Content-Type' => 'application/json'], $body));
        };
    }

    /**
     * @return callable(RequestInterface, array<string, mixed>): PromiseInterface
     */
    private function failWith(string $message): callable
    {
        return function (RequestInterface $request) use ($message): PromiseInterface {
            $this->handledRequest = $request;

            return Create::rejectionFor(new ConnectException($message, $request));
        };
    }

    /**
     * @param string[] $titles
     * @param array<string, mixed> $additionalResponseData
     */
    private function jobPostingsResponse(array $titles, array $additionalResponseData = []): string
    {
        $jobPostings = [];
        foreach ($titles as $index => $title) {
            $jobPostings[] = ['id' => $index + 1, 'title' => $title];
        }

        return (string)json_encode(['jobPostings' => $jobPostings, ...$additionalResponseData]);
    }

    /**
     * A complete plugin configuration, as FormEngine writes it once every field of
     * `Configuration/FlexForms/Core12|Core13/AcademicBiteJobsList.xml` has been touched.
     * A method rather than a constant, because PHP 8.1 does not allow constants in a
     * trait.
     *
     * @return array<string, string>
     */
    private function completeJobSettings(): array
    {
        return [
            'jobListingKey' => 'test-key',
            'view' => 'List',
            'sortBy' => 'title',
            'sortingDirection' => 'asc',
            'limit' => '',
        ];
    }

    /**
     * @param array<string, string> $jobSettings Overrides for `completeJobSettings()`.
     */
    private function requestWithPluginSettings(array $jobSettings = []): ServerRequestInterface
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->data = [
            'uid' => 1,
            'CType' => 'academicbitejobs_list',
            'pi_flexform' => $this->flexFormWithJobSettings(array_replace($this->completeJobSettings(), $jobSettings)),
        ];

        return (new ServerRequest())->withAttribute('currentContentObject', $contentObjectRenderer);
    }

    /**
     * @param array<string, string> $jobSettings
     */
    private function flexFormWithJobSettings(array $jobSettings): string
    {
        $fields = '';
        foreach ($jobSettings as $name => $value) {
            $fields .= sprintf(
                '<field index="settings.jobs.%s"><value index="vDEF">%s</value></field>',
                $name,
                htmlspecialchars($value),
            );
        }

        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
            . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
            . $fields
            . '</language></sheet></data></T3FlexForms>';
    }
}
