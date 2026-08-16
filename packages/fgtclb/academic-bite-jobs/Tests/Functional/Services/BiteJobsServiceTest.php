<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Services;

use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Direct coverage for `BiteJobsService::fetchBiteJobs()`, the single method behind the
 * `academicbitejobs_list` plugin. `Tests/Functional/Plugins/AcademicBiteJobsListPluginTest`
 * only proves the plugin renders; what the service sends to the b-ite API, and what it makes
 * of the answer, is pinned down here.
 *
 * Four things are worth knowing before reading the cases:
 *
 * - **No test performs an outgoing request.** Every case installs a Guzzle handler in
 *   `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']`, which `GuzzleClientFactory` uses
 *   verbatim instead of `HandlerStack::create()`. The handler answers from memory, and
 *   `backupGlobals` restores the setting after each test. Stubbing at handler level is the
 *   same technique the `test_bitejobs_stub` fixture extension uses, and for the same reason:
 *   `RequestFactory` and `GuzzleClientFactory` are `readonly` on TYPO3 v14 and plain classes
 *   on v13, so neither can be subclassed for both versions.
 * - The subject is built by hand rather than taken from the container, because the failure
 *   cases assert against the injected logger. `serviceIsResolvableFromTheDependencyInjectionContainer()`
 *   covers the wiring separately.
 * - The service reads its settings from the FlexForm of the content element it is rendered
 *   in, reached through the `currentContentObject` request attribute. `flexFormWithJobSettings()`
 *   builds that XML in the shape FormEngine stores it - see the `pi_flexform` column of
 *   `Tests/Functional/Plugins/Fixtures/AcademicBiteJobsListPlugin/biteJobsListPage.csv`.
 * - Deliberately not covered: a content element without a FlexForm, and a call without any
 *   request. Both reach `$settings['settings']['jobs']` unguarded and raise
 *   `Undefined array key` warnings, which this suite turns into failures. That is a defect in
 *   the service, not something a test should pin down.
 *
 * The `@var` annotations on the return value of `fetchBiteJobs()` are not decoration: the
 * method declares `@return string[]` while it returns the decoded job postings, one array per
 * posting. Without the override PHPStan reads every assertion against a posting as comparing
 * a string to an array, and reports the assertions as always false.
 */
final class BiteJobsServiceTest extends AbstractAcademicBiteJobsTestCase
{
    /**
     * A complete plugin configuration, as FormEngine writes it once every field of
     * `Configuration/FlexForms/AcademicBiteJobsList.xml` has been touched.
     *
     * @var array<string, string>
     */
    private const COMPLETE_JOB_SETTINGS = [
        'jobListingKey' => 'test-key',
        'view' => 'List',
        'sortBy' => 'title',
        'sortingDirection' => 'asc',
        'limit' => '',
    ];

    /**
     * The request the stub handler was asked to answer, for the cases asserting the payload.
     */
    private ?RequestInterface $handledRequest = null;

    #[Test]
    public function jobPostingsOfTheApiResponseAreReturned(): void
    {
        /** @var array<int, array<string, mixed>> $jobs */
        $jobs = $this->buildSubject($this->respondWith(200, $this->jobPostingsResponse(['First', 'Second'])))
            ->fetchBiteJobs($this->requestWithPluginSettings());

        $this->assertSame(
            [
                ['id' => 1, 'title' => 'First'],
                ['id' => 2, 'title' => 'Second'],
            ],
            $jobs,
        );
    }

    /**
     * The payload is the service's whole contract with the b-ite API, and four of its six
     * keys are hardcoded - only `key` and `sort` come from the plugin. Renaming a FlexForm
     * field or reordering the sort keys silently returns an unsorted list in production;
     * here it fails.
     */
    #[Test]
    public function pluginSettingsAreSentAsJsonPayloadToTheSearchEndpoint(): void
    {
        $this->buildSubject($this->respondWith(200, $this->jobPostingsResponse(['First'])))
            ->fetchBiteJobs($this->requestWithPluginSettings([
                'jobListingKey' => 'acme-listing',
                'sortBy' => 'endsOn',
                'sortingDirection' => 'desc',
            ]));

        $handledRequest = $this->handledRequest();
        $this->assertSame('POST', $handledRequest->getMethod());
        $this->assertSame(
            'https://jobs.b-ite.com/api/v1/postings/search',
            (string)$handledRequest->getUri(),
        );
        $this->assertSame('application/json', $handledRequest->getHeaderLine('Content-Type'));
        $this->assertSame(
            [
                'key' => 'acme-listing',
                'channel' => 0,
                'locale' => 'de',
                'page' => ['offset' => 0],
                'filter' => [],
                'sort' => ['order' => 'desc', 'by' => 'endsOn'],
            ],
            json_decode((string)$handledRequest->getBody(), true),
        );
    }

    /**
     * The limit is applied after the response arrived, not sent along with it, so the API
     * always answers with everything the listing holds.
     *
     * `'0'` is the case worth having: the service guards the slice with `empty()`, so a
     * limit of zero does not return zero postings - it returns all of them.
     *
     * @param string[] $expectedTitles
     */
    #[Test]
    #[DataProvider('limitAppliedToTheReturnedPostingsDataProvider')]
    public function limitIsAppliedToTheReturnedPostings(string $limit, array $expectedTitles): void
    {
        /** @var array<int, array<string, mixed>> $jobs */
        $jobs = $this->buildSubject($this->respondWith(200, $this->jobPostingsResponse(['First', 'Second', 'Third'])))
            ->fetchBiteJobs($this->requestWithPluginSettings(['limit' => $limit]));

        $this->assertSame($expectedTitles, array_column($jobs, 'title'));
    }

    /**
     * @return \Generator<string, array{limit: string, expectedTitles: string[]}>
     */
    public static function limitAppliedToTheReturnedPostingsDataProvider(): \Generator
    {
        yield 'an unset limit returns every posting' => [
            'limit' => '',
            'expectedTitles' => ['First', 'Second', 'Third'],
        ];
        yield 'a limit of zero returns every posting' => [
            'limit' => '0',
            'expectedTitles' => ['First', 'Second', 'Third'],
        ];
        yield 'a limit below the number of postings cuts the tail' => [
            'limit' => '2',
            'expectedTitles' => ['First', 'Second'],
        ];
        yield 'a limit of one keeps the first posting only' => [
            'limit' => '1',
            'expectedTitles' => ['First'],
        ];
        yield 'a limit above the number of postings returns every posting' => [
            'limit' => '10',
            'expectedTitles' => ['First', 'Second', 'Third'],
        ];
    }

    /**
     * A listing key that exists but holds nothing answers with an empty `jobPostings`
     * array - the normal state of a listing between two recruiting rounds, not an error.
     */
    #[Test]
    public function responseWithoutJobPostingsYieldsAnEmptyList(): void
    {
        $jobs = $this->buildSubject($this->respondWith(200, '{"jobPostings":[]}'))
            ->fetchBiteJobs($this->requestWithPluginSettings());

        $this->assertSame([], $jobs);
    }

    /**
     * An unknown listing key answers `200` with a body that carries no `jobPostings` key at
     * all. The service must not fail on the missing key.
     */
    #[Test]
    public function responseOfAnUnknownStructureYieldsAnEmptyList(): void
    {
        $jobs = $this->buildSubject($this->respondWith(200, '{"errors":[{"code":"unknown-key"}]}'))
            ->fetchBiteJobs($this->requestWithPluginSettings());

        $this->assertSame([], $jobs);
    }

    /**
     * A body that is not JSON - a proxy error page, say - decodes to `null`. It is the one
     * failure that is not logged, because `json_decode()` reports it by return value and the
     * service only logs what `RequestFactory` throws.
     */
    #[Test]
    public function responseThatIsNotJsonYieldsAnEmptyListWithoutBeingLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $jobs = $this->buildSubject($this->respondWith(200, '<html><body>Gateway timeout</body></html>'), $logger)
            ->fetchBiteJobs($this->requestWithPluginSettings());

        $this->assertSame([], $jobs);
    }

    /**
     * Guzzle raises on a `5xx`, so an API outage arrives as an exception. The plugin has to
     * keep rendering, which is what the caught exception buys - at the price of the failure
     * being visible in the log only.
     */
    #[Test]
    public function serverErrorIsLoggedAndYieldsAnEmptyList(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Error while fetching jobs from Bite API: '));

        $jobs = $this->buildSubject($this->respondWith(500, 'Internal Server Error'), $logger)
            ->fetchBiteJobs($this->requestWithPluginSettings());

        $this->assertSame([], $jobs);
    }

    /**
     * A host that cannot be reached at all never produces a response, so the code path
     * differs from the `5xx` one above - it is the only one where `$responseBody` keeps the
     * value it had before the call.
     */
    #[Test]
    public function connectionFailureIsLoggedAndYieldsAnEmptyList(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Error while fetching jobs from Bite API: Connection refused');

        $jobs = $this->buildSubject($this->failWith('Connection refused'), $logger)
            ->fetchBiteJobs($this->requestWithPluginSettings());

        $this->assertSame([], $jobs);
    }

    /**
     * Documents current behaviour, not desired behaviour, and the service says so itself:
     * `$responseBody` carries the last response on the instance, flagged with a `@todo`
     * calling it a really bad idea. A second call that fails therefore answers with the
     * postings of the first one instead of with an empty list. It only surfaces where the
     * service is reused - it is not `shared: false` - and it is the reason the `@todo` is
     * worth acting on.
     */
    #[Test]
    public function failedCallRepeatsThePostingsOfThePreviousCall(): void
    {
        $subject = $this->buildSubject($this->respondWith(200, $this->jobPostingsResponse(['First'])));
        $request = $this->requestWithPluginSettings();

        /** @var array<int, array<string, mixed>> $jobsOfTheFirstCall */
        $jobsOfTheFirstCall = $subject->fetchBiteJobs($request);
        $this->assertSame(['First'], array_column($jobsOfTheFirstCall, 'title'));

        $this->installHandler($this->failWith('Connection refused'));

        /** @var array<int, array<string, mixed>> $jobsOfTheSecondCall */
        $jobsOfTheSecondCall = $subject->fetchBiteJobs($request);
        $this->assertSame(['First'], array_column($jobsOfTheSecondCall, 'title'));
    }

    /**
     * The argument is optional and defaults to the global request, which is how the service
     * behaved before the controller started passing its own request. Both routes have to
     * reach the same content object.
     */
    #[Test]
    public function globalRequestIsUsedWhenNoRequestIsPassed(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = $this->requestWithPluginSettings(['jobListingKey' => 'from-global-request']);

        /** @var array<int, array<string, mixed>> $jobs */
        $jobs = $this->buildSubject($this->respondWith(200, $this->jobPostingsResponse(['First'])))
            ->fetchBiteJobs();
        $payload = json_decode((string)$this->handledRequest()->getBody(), true);

        $this->assertSame(['First'], array_column($jobs, 'title'));
        $this->assertIsArray($payload);
        $this->assertSame('from-global-request', $payload['key'] ?? null);
    }

    /**
     * The service is autowired through `Configuration/Services.yaml` and injected into
     * `BiteJobsController` by type. Every other case builds it by hand, so nothing else here
     * would notice a broken constructor signature.
     */
    #[Test]
    public function serviceIsResolvableFromTheDependencyInjectionContainer(): void
    {
        $this->assertInstanceOf(BiteJobsService::class, $this->get(BiteJobsService::class));
    }

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
     * @param callable(RequestInterface, array<string, mixed>): PromiseInterface $handler
     */
    private function buildSubject(callable $handler, ?LoggerInterface $logger = null): BiteJobsService
    {
        $this->installHandler($handler);

        return new BiteJobsService(
            $this->get(RequestFactory::class),
            $logger ?? new NullLogger(),
        );
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
     */
    private function jobPostingsResponse(array $titles): string
    {
        $jobPostings = [];
        foreach ($titles as $index => $title) {
            $jobPostings[] = ['id' => $index + 1, 'title' => $title];
        }

        return (string)json_encode(['jobPostings' => $jobPostings]);
    }

    /**
     * @param array<string, string> $jobSettings Overrides for `self::COMPLETE_JOB_SETTINGS`.
     */
    private function requestWithPluginSettings(array $jobSettings = []): ServerRequestInterface
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->data = [
            'uid' => 1,
            'CType' => 'academicbitejobs_list',
            'pi_flexform' => $this->flexFormWithJobSettings(array_replace(self::COMPLETE_JOB_SETTINGS, $jobSettings)),
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
