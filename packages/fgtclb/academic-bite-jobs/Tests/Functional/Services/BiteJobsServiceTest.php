<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Services;

use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use FGTCLB\AcademicBiteJobs\Tests\Functional\BiteJobsApiStubTrait;
use GuzzleHttp\Promise\PromiseInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;
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
 * of the answer, is pinned down here. No listener of the two events of the service is
 * installed, so every case shows the request and the postings as they are without one.
 * `Tests/Functional/Event/BiteJobsEventsTest` covers the listeners.
 *
 * How the API is stubbed and where the service reads its settings from is described on
 * `BiteJobsApiStubTrait`. Two more things are worth knowing before reading the cases:
 *
 * - The subject is built by hand rather than taken from the container, because the failure
 *   cases assert against the injected logger. `serviceIsResolvableFromTheDependencyInjectionContainer()`
 *   covers the wiring separately.
 * - A content element without a stored FlexForm sends the request with empty values. That
 *   is pinned down because it used to raise `Undefined array key` warnings, and a warning
 *   is a failure in this suite.
 */
final class BiteJobsServiceTest extends AbstractAcademicBiteJobsTestCase
{
    use BiteJobsApiStubTrait;

    #[Test]
    public function jobPostingsOfTheApiResponseAreReturned(): void
    {
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
     * differs from the `5xx` one above.
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
     * B-ITE answers one array per posting in a list. Anything else in `jobPostings` is left
     * out, and a keyed answer is handed on as a list, so the templates and the listeners
     * always get a list of postings.
     */
    #[Test]
    public function jobPostingsThatAreNotArraysAreLeftOut(): void
    {
        $jobs = $this->buildSubject($this->respondWith(200, '{"jobPostings":{"a":{"id":1,"title":"First"},"b":"Second","c":{"id":3,"title":"Third"}}}'))
            ->fetchBiteJobs($this->requestWithPluginSettings());

        $this->assertSame(
            [
                ['id' => 1, 'title' => 'First'],
                ['id' => 3, 'title' => 'Third'],
            ],
            $jobs,
        );
    }

    /**
     * A content element created by an import, for example, has no FlexForm stored. The
     * request goes out with empty values and the list renders what the API answers.
     */
    #[Test]
    public function contentElementWithoutFlexFormSendsTheRequestWithEmptyValues(): void
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->data = ['uid' => 1, 'CType' => 'academicbitejobs_list', 'pi_flexform' => ''];

        $jobs = $this->buildSubject($this->respondWith(200, '{"jobPostings":[]}'))
            ->fetchBiteJobs((new ServerRequest())->withAttribute('currentContentObject', $contentObjectRenderer));

        $this->assertSame([], $jobs);
        $this->assertSame(
            [
                'key' => null,
                'channel' => 0,
                'locale' => 'de',
                'page' => ['offset' => 0],
                'filter' => [],
                'sort' => ['order' => null, 'by' => null],
            ],
            $this->handledPayload(),
        );
    }

    /**
     * The service is shared, so two job lists on one page are served by the same instance.
     * A second request that fails has to render no postings, not the postings of the first
     * one, which the service kept on its instance before 2.4.
     */
    #[Test]
    public function failedCallAfterASuccessfulOneReturnsNoPostings(): void
    {
        $subject = $this->buildSubject($this->respondWith(200, $this->jobPostingsResponse(['First'])));
        $request = $this->requestWithPluginSettings();

        $jobsOfTheFirstCall = $subject->fetchBiteJobs($request);
        $this->assertSame(['First'], array_column($jobsOfTheFirstCall, 'title'));

        $this->installHandler($this->failWith('Connection refused'));

        $this->assertSame([], $subject->fetchBiteJobs($request));
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

        $jobs = $this->buildSubject($this->respondWith(200, $this->jobPostingsResponse(['First'])))
            ->fetchBiteJobs();

        $this->assertSame(['First'], array_column($jobs, 'title'));
        $this->assertSame('from-global-request', $this->handledPayload()['key'] ?? null);
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
     * @param callable(RequestInterface, array<string, mixed>): PromiseInterface $handler
     */
    private function buildSubject(callable $handler, ?LoggerInterface $logger = null): BiteJobsService
    {
        $this->installHandler($handler);

        return new BiteJobsService(
            $this->get(RequestFactory::class),
            $logger ?? new NullLogger(),
            $this->get(EventDispatcherInterface::class),
        );
    }
}
