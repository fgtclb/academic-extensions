<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Shared setup for functional tests that render a plugin in the frontend.
 *
 * Every plugin rendering test needs the same scaffolding — an instance configuration
 * that surfaces sub request errors, a site to request, a way to fire that request and a
 * teardown that removes the written site configuration again. Written out per test class
 * that is roughly sixty lines before the first assertion, which is why it moved here.
 *
 * The test class has to use `SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait` as
 * well and declare its own `LANGUAGE_PRESETS`: which languages a test needs is part of
 * what it tests, so it stays with the test.
 *
 * Typical use:
 *
 * ```php
 * protected function setUp(): void
 * {
 *     $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
 *     $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
 *     parent::setUp();
 * }
 *
 * protected function tearDown(): void
 * {
 *     $this->removeWrittenSiteConfiguration();
 *     parent::tearDown();
 * }
 * ```
 */
trait FrontendPluginRenderingTrait
{
    /**
     * Base URL of the site the helpers below write and request.
     *
     * A method rather than a constant, because this branch supports PHP 8.1 and
     * constants in traits arrived in PHP 8.2 - a trait constant is a fatal
     * "Traits cannot have constants" there, at parse time, before any test runs.
     * Override it in a test class that needs a different host.
     */
    protected function frontendPluginTestBase(): string
    {
        return 'https://www.acme.com/';
    }

    /**
     * `subrequestPageErrors` is what makes a failing plugin fail the test: without it the
     * frontend swallows the exception of a sub request and answers a rendered error page,
     * so an assertion on the status code alone would pass.
     *
     * @param array<string, mixed> $additionalConfiguration Merged recursively, so a single
     *        key can be added to `FE` without repeating the rest of it.
     * @return array<string, mixed>
     */
    protected function frontendPluginTestConfiguration(array $additionalConfiguration = []): array
    {
        $configuration = [
            'SYS' => [
                'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
                'features' => [
                    'subrequestPageErrors' => true,
                ],
            ],
            'FE' => [
                'debug' => false,
            ],
        ];

        return array_replace_recursive($configuration, $additionalConfiguration);
    }

    protected function addCoreExtensionsToLoad(string ...$extensionKeys): void
    {
        $this->coreExtensionsToLoad = array_values(array_unique([
            ...array_values($this->coreExtensionsToLoad),
            ...$extensionKeys,
        ]));
    }

    protected function addTestExtensionsToLoad(string ...$extensionPaths): void
    {
        $this->testExtensionsToLoad = array_values(array_unique([
            ...array_values($this->testExtensionsToLoad),
            ...$extensionPaths,
        ]));
    }

    /**
     * A written site configuration outlives the test instance, so it has to be removed
     * explicitly — otherwise the next test finds a site it did not write.
     */
    protected function removeWrittenSiteConfiguration(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
    }

    /**
     * Writes the site every helper here assumes, identified as `acme`.
     *
     * @param array<int, array<string, mixed>> $languages Build them with
     *        `buildDefaultLanguageConfiguration()` / `buildLanguageConfiguration()`.
     */
    protected function writeFrontendPluginTestSite(array $languages, int $rootPageId = 1): void
    {
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: $rootPageId,
                base: $this->frontendPluginTestBase(),
            ),
            languages: $languages,
        );
    }

    /**
     * @param string|InternalRequest $request An absolute URL, or a prepared request when the
     *        test needs anything beyond a plain GET.
     */
    protected function requestFrontendPage(
        string|InternalRequest $request,
        ?InternalRequestContext $context = null,
    ): ResponseInterface {
        return $this->executeFrontendSubRequest(
            is_string($request) ? new InternalRequest($request) : $request,
            $context ?? new InternalRequestContext(),
        );
    }

    /**
     * Requests a page and returns its body, failing the test when the request did not
     * answer `200` — which is what a plugin exception looks like from the outside.
     */
    protected function renderFrontendPage(
        string|InternalRequest $request,
        ?InternalRequestContext $context = null,
    ): string {
        $response = $this->requestFrontendPage($request, $context);
        $this->assertSame(
            200,
            $response->getStatusCode(),
            sprintf(
                'Request to "%s" failed.',
                is_string($request) ? $request : (string)$request->getUri(),
            ),
        );

        return (string)$response->getBody();
    }
}
