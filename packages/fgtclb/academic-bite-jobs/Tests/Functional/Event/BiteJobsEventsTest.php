<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent;
use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent;
use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use FGTCLB\AcademicBiteJobs\Tests\Functional\BiteJobsApiStubTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TESTS\TestBitejobsListener\EventListener\RecordBiteJobsEvents;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Covers the two events of `BiteJobsService` through the listeners of the
 * `test_bitejobs_listener` fixture extension, which act only on the settings a case gives
 * them: `ModifyBiteJobPostingsRequestEvent` changes what is sent to B-ITE, and
 * `ModifyBiteJobPostingsEvent` changes the postings the job list renders.
 *
 * Most cases call the service, with the API stubbed as described on `BiteJobsApiStubTrait`,
 * and assert the request that left and the postings that came back. The last two render the
 * job list in the frontend, answered by the `test_bitejobs_stub` fixture extension, and show
 * that the plugin hands both events and its view event one context: a listener reads the
 * TypoScript settings of the plugin from it and groups the list by what it wrote.
 */
final class BiteJobsEventsTest extends AbstractAcademicBiteJobsTestCase
{
    use BiteJobsApiStubTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-bitejobs-stub', 'tests/test-bitejobs-listener');
        parent::setUp();
        RecordBiteJobsEvents::$events = [];
    }

    protected function tearDown(): void
    {
        RecordBiteJobsEvents::$events = [];
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function requestListenerAddsACustomFieldFilter(): void
    {
        $this->installHandler($this->respondWith(200, $this->jobPostingsResponse(['First'])));

        $this->get(BiteJobsService::class)->fetchBiteJobs($this->requestWithPluginSettings(['testZuordnung' => '01']));

        $this->assertSame(
            [
                'key' => 'test-key',
                'channel' => 0,
                'locale' => 'de',
                'page' => ['offset' => 0],
                'filter' => ['custom.zuordnung' => ['in' => ['01']]],
                'sort' => ['order' => 'asc', 'by' => 'title'],
            ],
            $this->handledPayload(),
        );
    }

    #[Test]
    public function requestListenerSetsTheLocale(): void
    {
        $this->installHandler($this->respondWith(200, $this->jobPostingsResponse(['First'])));

        $this->get(BiteJobsService::class)->fetchBiteJobs($this->requestWithPluginSettings(['testLocale' => 'en']));

        $this->assertSame('en', $this->handledPayload()['locale'] ?? null);
    }

    /**
     * The settings are the plugin settings below `settings.jobs`, the ones the payload is
     * built from. The context is the one the caller passed.
     */
    #[Test]
    public function requestEventCarriesTheSettingsTheRequestAndThePluginContext(): void
    {
        $this->installHandler($this->respondWith(200, $this->jobPostingsResponse(['First'])));
        $request = $this->requestWithPluginSettings(['jobListingKey' => 'acme-listing']);
        $context = new PluginControllerActionContext($request, []);

        $this->get(BiteJobsService::class)->fetchBiteJobs($request, $context);

        $event = $this->recordedEvent(ModifyBiteJobPostingsRequestEvent::class);
        $this->assertSame('acme-listing', $event->getSettings()['jobListingKey'] ?? null);
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($context, $event->getPluginControllerActionContext());
    }

    #[Test]
    public function resultListenerRemovesAPosting(): void
    {
        $this->installHandler($this->respondWith(200, $this->jobPostingsResponse(['First', 'Second', 'Third'])));

        $jobs = $this->get(BiteJobsService::class)
            ->fetchBiteJobs($this->requestWithPluginSettings(['testRemoveTitle' => 'Second']));

        $this->assertSame(['First', 'Third'], array_column($jobs, 'title'));
    }

    /**
     * The listener adds nothing without a plugin context, so the relation names prove that the
     * context the caller passed reached it.
     */
    #[Test]
    public function resultListenerAddsARelationNameFromThePluginContext(): void
    {
        $this->installHandler($this->respondWith(200, (string)json_encode([
            'jobPostings' => [
                ['id' => 1, 'title' => 'First', 'department' => 'Research'],
                ['id' => 2, 'title' => 'Second', 'department' => 'Teaching'],
            ],
        ])));
        $request = $this->requestWithPluginSettings();
        $context = new PluginControllerActionContext($request, [
            'testRelationNames' => ['Research' => 'Faculty of Research', 'Teaching' => 'Faculty of Teaching'],
        ]);

        $jobs = $this->get(BiteJobsService::class)->fetchBiteJobs($request, $context);

        $this->assertSame(['Faculty of Research', 'Faculty of Teaching'], array_column($jobs, 'relationName'));
    }

    /**
     * Four postings, the listener removes the first one, and the limit of two keeps the second
     * and the third. Applied before the listener, the limit would keep the first two and the
     * listener would leave the second one alone.
     */
    #[Test]
    public function limitAppliesToThePostingsTheListenerReturns(): void
    {
        $this->installHandler($this->respondWith(200, $this->jobPostingsResponse(['First', 'Second', 'Third', 'Fourth'])));

        $jobs = $this->get(BiteJobsService::class)
            ->fetchBiteJobs($this->requestWithPluginSettings(['testRemoveTitle' => 'First', 'limit' => '2']));

        $this->assertSame(['Second', 'Third'], array_column($jobs, 'title'));
    }

    #[Test]
    public function resultEventCarriesTheDecodedResponse(): void
    {
        $this->installHandler($this->respondWith(200, $this->jobPostingsResponse(['First'], ['total' => 1])));

        $this->get(BiteJobsService::class)->fetchBiteJobs($this->requestWithPluginSettings());

        $this->assertSame(
            ['jobPostings' => [['id' => 1, 'title' => 'First']], 'total' => 1],
            $this->recordedEvent(ModifyBiteJobPostingsEvent::class)->getResponseData(),
        );
    }

    /**
     * A failed request dispatches the result event too, with no postings and no response
     * data, and what the listener returns is rendered.
     */
    #[Test]
    public function resultEventIsDispatchedAfterAFailedRequest(): void
    {
        $this->installHandler($this->failWith('Connection refused'));

        $jobs = $this->get(BiteJobsService::class)
            ->fetchBiteJobs($this->requestWithPluginSettings(['testFallbackTitle' => 'Fallback']));

        $this->assertSame(['Fallback'], array_column($jobs, 'title'));
        $this->assertSame([], $this->recordedEvent(ModifyBiteJobPostingsEvent::class)->getResponseData());
    }

    /**
     * A payload that cannot be encoded as JSON is never sent. It is logged like a failed
     * request, naming the JSON error, and the result event is dispatched as after one.
     */
    #[Test]
    public function payloadThatCannotBeEncodedIsLoggedAndNotSent(): void
    {
        $this->installHandler($this->respondWith(200, $this->jobPostingsResponse(['First'])));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringStartsWith('Error while fetching jobs from Bite API: '),
                $this->stringContains('Malformed UTF-8'),
            ));
        $subject = new BiteJobsService(
            $this->get(RequestFactory::class),
            $logger,
            $this->get(EventDispatcherInterface::class),
        );

        $jobs = $subject->fetchBiteJobs($this->requestWithPluginSettings([
            'testBreakPayload' => '1',
            'testFallbackTitle' => 'Fallback',
        ]));

        $this->assertNull($this->handledRequest);
        $this->assertSame(['Fallback'], array_column($jobs, 'title'));
    }

    /**
     * The stubbed postings carry the departments "Research" and "Teaching", which the
     * TypoScript of the fixture extension maps to relation names and groups the list by.
     */
    #[Test]
    public function jobListIsGroupedByTheRelationNameAListenerWrites(): void
    {
        $this->setUpFrontend();

        $html = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['Faculty of Research', 'Faculty of Teaching'],
            $this->textsOf($html, '//div[contains(@class, "academic-bite-jobs-list")]/h2'),
        );
    }

    #[Test]
    public function jobListHandsOneContextToBothEventsAndThePluginView(): void
    {
        $this->setUpFrontend();

        $this->renderFrontendPage('https://www.acme.com/home');

        $requestContext = $this->recordedEvent(ModifyBiteJobPostingsRequestEvent::class)->getPluginControllerActionContext();
        $resultContext = $this->recordedEvent(ModifyBiteJobPostingsEvent::class)->getPluginControllerActionContext();
        $viewContext = $this->recordedEvent(ModifyPluginViewEvent::class)->getPluginControllerActionContext();
        $this->assertNotNull($requestContext);
        $this->assertSame($requestContext, $resultContext);
        $this->assertSame($requestContext, $viewContext);
        $this->assertSame('List', $requestContext->getPluginName());
        $this->assertSame(1, $requestContext->getContentObjectRenderer()?->data['uid'] ?? null);
    }

    private function setUpFrontend(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Plugins/Fixtures/AcademicBiteJobsListPlugin/biteJobsListPage.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/List/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/List/setup.typoscript',
                    'EXT:academic_bite_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    'EXT:test_bitejobs_listener/Configuration/TypoScript/RelationNames.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    /**
     * The one event of the class the listeners recorded, asserted to be the only one.
     *
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    private function recordedEvent(string $className): object
    {
        $events = array_values(array_filter(
            RecordBiteJobsEvents::$events,
            static fn(object $event): bool => $event instanceof $className,
        ));
        $this->assertCount(1, $events);
        $this->assertInstanceOf($className, $events[0]);

        return $events[0];
    }

    /**
     * @return string[] The trimmed text of every node the expression selects, in document order.
     */
    private function textsOf(string $html, string $expression): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $texts = [];
        $nodes = (new \DOMXPath($document))->query($expression);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);
        foreach ($nodes as $node) {
            $texts[] = trim($node->textContent);
        }

        return $texts;
    }
}
