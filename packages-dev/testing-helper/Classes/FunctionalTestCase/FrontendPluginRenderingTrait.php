<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
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
     */
    protected const FRONTEND_PLUGIN_TEST_BASE = 'https://www.acme.com/';

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
                base: self::FRONTEND_PLUGIN_TEST_BASE,
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

    /**
     * Requests a page, submits one of its POST forms and returns the response of the
     * submission. A redirect is returned, not followed.
     *
     * The fields are collected as a browser collects them for the controls these forms
     * use: hidden and text-like inputs, checked checkboxes and radios, selects with their
     * selected options - a single select without one sends its first enabled option, a
     * multiple select sends nothing - and textareas. Disabled controls, disabled options
     * and buttons are left out.
     * `__referrer` and `__trustedProperties` are sent as rendered, so Extbase checks the
     * request hash of the form exactly as it does in production.
     *
     * `$values` then replaces what the form holds, nested like the parsed body:
     * `['tx_ext_plugin' => ['demand' => ['sortingField' => 'title']]]`. Each of them has to
     * name a field the form rendered - a value the form does not offer can be sent that
     * way, a field it does not have cannot, so a test fails when the form's field names
     * change. A request with fields of its own goes through `frontendPostRequest()`.
     *
     * @param string $formClass A class of the `<form>` element; exactly one form of the
     *        page must carry it.
     * @param array<string, mixed> $values
     */
    protected function submitFrontendForm(string $pageUrl, string $formClass, array $values = []): ResponseInterface
    {
        $document = new \DOMDocument();
        $document->loadHTML(
            '<?xml encoding="UTF-8">' . $this->renderFrontendPage($pageUrl),
            LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        $xpath = new \DOMXPath($document);
        $forms = $xpath->query(
            sprintf('//form[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $formClass),
        );
        $this->assertNotFalse($forms);
        $this->assertCount(1, $forms, sprintf('The page "%s" has no single form of class "%s".', $pageUrl, $formClass));
        $form = $forms->item(0);
        $this->assertInstanceOf(\DOMElement::class, $form);
        $this->assertSame('post', strtolower($form->getAttribute('method')), 'Only a POST form can be submitted.');

        $fields = [];
        foreach ($xpath->query('.//input[@name] | .//select[@name] | .//textarea[@name]', $form) ?: [] as $field) {
            if ($field instanceof \DOMElement && !$field->hasAttribute('disabled')) {
                foreach ($this->submittedFormFieldValues($field) as $value) {
                    $fields[] = rawurlencode($field->getAttribute('name')) . '=' . rawurlencode($value);
                }
            }
        }
        parse_str(implode('&', $fields), $parsedBody);
        $this->assertFormRendersFields($parsedBody, $values);

        $action = $form->getAttribute('action');
        return $this->requestFrontendPage($this->frontendPostRequest(
            str_starts_with($action, '/') ? rtrim(self::FRONTEND_PLUGIN_TEST_BASE, '/') . $action : $action,
            array_replace_recursive($parsedBody, $values),
        ));
    }

    /**
     * @return list<string>
     */
    private function submittedFormFieldValues(\DOMElement $field): array
    {
        if ($field->tagName === 'textarea') {
            return [$field->textContent];
        }
        if ($field->tagName === 'input') {
            $type = strtolower($field->getAttribute('type') ?: 'text');
            if (in_array($type, ['submit', 'button', 'image', 'reset', 'file'], true)) {
                return [];
            }
            if (in_array($type, ['checkbox', 'radio'], true)) {
                return $field->hasAttribute('checked') ? [$field->hasAttribute('value') ? $field->getAttribute('value') : 'on'] : [];
            }
            return [$field->getAttribute('value')];
        }

        $values = [];
        $firstValue = null;
        foreach ($field->getElementsByTagName('option') as $option) {
            if ($option->hasAttribute('disabled')) {
                continue;
            }
            $value = $option->hasAttribute('value') ? $option->getAttribute('value') : trim($option->textContent);
            $firstValue ??= $value;
            if ($option->hasAttribute('selected')) {
                $values[] = $value;
            }
        }
        if ($values === [] && $firstValue !== null && !$field->hasAttribute('multiple')) {
            $values[] = $firstValue;
        }

        return $values;
    }

    /**
     * @param array<mixed> $rendered
     * @param array<mixed> $values
     */
    private function assertFormRendersFields(array $rendered, array $values, string $path = ''): void
    {
        foreach ($values as $key => $value) {
            $fieldPath = $path === '' ? (string)$key : $path . '[' . $key . ']';
            $this->assertArrayHasKey($key, $rendered, sprintf('The form renders no field "%s".', $fieldPath));
            if (is_array($value) && is_array($rendered[$key])) {
                $this->assertFormRendersFields($rendered[$key], $value, $fieldPath);
            }
        }
    }

    /**
     * A POST request carrying `$parsedBody` as a form encoded body.
     *
     * The body is written explicitly: the testing framework otherwise serialises the
     * parsed body with `GuzzleHttp\Psr7\Query::build()`, which cannot handle nested plugin
     * arguments and emits an "Array to string conversion" warning.
     *
     * @param array<string, mixed> $parsedBody
     */
    protected function frontendPostRequest(string $url, array $parsedBody): InternalRequest
    {
        $body = new Stream('php://temp', 'rw');
        $body->write(http_build_query($parsedBody));
        $body->rewind();

        return (new InternalRequest($url))
            ->withMethod('POST')
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body)
            ->withParsedBody($parsedBody);
    }

    /**
     * Asserts a `303 See Other` to an absolute URL of the test site carrying a cache hash,
     * and returns that URL - what `ActionController::redirect()` answers for a plugin
     * action, whose `action` and `controller` arguments the cache hash covers.
     */
    protected function assertSeeOtherWithCacheHash(ResponseInterface $response): string
    {
        $this->assertSame(303, $response->getStatusCode(), (string)$response->getBody());
        $location = $response->getHeaderLine('Location');
        $this->assertStringStartsWith(self::FRONTEND_PLUGIN_TEST_BASE, $location);
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $this->assertIsString($query['cHash'] ?? null, sprintf('"%s" carries no cHash.', $location));
        $this->assertNotSame('', $query['cHash']);

        return $location;
    }
}
