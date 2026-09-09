<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Submits the `academicjobs_newjobform` plugin with values its validation set refuses
 * and pins what the visitor gets back.
 *
 * This is the path the success tests never take, and the one that was broken:
 * `Job/Forms/Errors` was rendered with `<f:form.validationResults for="{object}">`,
 * where `object` was the `Job` model itself. That argument is a property path, not an
 * object, so an invalid submission answered a `TypeError` instead of the form.
 *
 * It stayed unnoticed because both halves of it have to meet: a submission has to be
 * refused - a valid one redirects and never renders the partial again - and a form
 * object has to be bound, which `JobController::newAction()` does not do on its own.
 * Without one, `{object}` resolves to nothing and the argument happens to be an empty
 * string. This test therefore loads `test_jobs_form_prefill`, which binds the stored
 * job the way an integrator does.
 *
 * What is asserted is therefore the whole loop: the refusal is a rendered form again,
 * the summary above it is shown, the refused field is marked, and nothing was written.
 */
final class AcademicJobsNewJobFormValidationErrorsTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'MAIL' => [
                'transport' => 'null',
            ],
        ]);
        // The defect is only reachable while a form object is bound: without one,
        // `{object}` resolves to nothing and `for=""` happens to select the whole
        // result set. `test_jobs_form_prefill` binds the stored job through
        // `ModifyJobControllerNewActionViewEvent`, which is the documented way an
        // integrator offers a record for editing - and the shape in which the
        // partial was handed the object rather than its name.
        $this->addTestExtensionsToLoad('tests/test-jobs-form-prefill');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNewJobFormDatePrefill/prefilledJobPage.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
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
     * Renders the form and returns its action URI together with its hidden fields.
     * Both carry request bound values - the action selects the controller and action,
     * the hidden fields carry the referrer and the trusted properties hash - and can
     * therefore not be hardcoded.
     *
     * @return array{action: string, fields: array<string, string>}
     */
    private function renderFormAndExtractSubmitData(): array
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(1, preg_match('@<form [^>]*action="([^"]+)"@', $content, $actionMatch));

        $fields = [];
        preg_match_all(
            '@<input[^>]+type="hidden"[^>]+name="([^"]+)"[^>]+value="([^"]*)"@',
            $content,
            $matches,
            PREG_SET_ORDER
        );
        foreach ($matches as $match) {
            $fields[html_entity_decode($match[1])] = html_entity_decode($match[2]);
        }
        $this->assertNotEmpty($fields, 'Job form contains no hidden fields.');

        return [
            'action' => html_entity_decode($actionMatch[1]),
            'fields' => $fields,
        ];
    }

    /**
     * @param array<string, string> $values
     */
    private function submitJobForm(array $values): ResponseInterface
    {
        $submitData = $this->renderFormAndExtractSubmitData();
        $parsedBody = $this->pluginArgumentsOfFormAction($submitData['action']);
        foreach ($submitData['fields'] as $name => $value) {
            $this->addFormValue($parsedBody, $name, $value);
        }
        foreach ($values as $property => $value) {
            $this->addFormValue($parsedBody, 'tx_academicjobs_newjobform[job][' . $property . ']', $value);
        }

        // The body is provided explicitly. The testing framework otherwise serialises
        // the parsed body with `GuzzleHttp\Psr7\Query::build()`, which cannot handle
        // the nested plugin arguments and emits an "Array to string conversion".
        $body = new Stream('php://temp', 'rw');
        $body->write(http_build_query($parsedBody));
        $body->rewind();

        return $this->requestFrontendPage(
            (new InternalRequest('https://www.acme.com/home'))
                ->withMethod('POST')
                ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($body)
                ->withParsedBody($parsedBody)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function pluginArgumentsOfFormAction(string $action): array
    {
        $query = parse_url($action, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return [];
        }
        $parsed = [];
        parse_str($query, $parsed);

        $arguments = [];
        foreach ($parsed as $name => $value) {
            $arguments[(string)$name] = $value;
        }

        return $arguments;
    }

    /**
     * Turns `a[b][c]` notation into the nested array the request expects.
     *
     * @param array<string, mixed> $target
     */
    private function addFormValue(array &$target, string $name, string $value): void
    {
        $position = strpos($name, '[');
        if ($position === false) {
            $target[$name] = $value;
            return;
        }
        preg_match_all('@\[([^]]*)]@', $name, $matches);
        $keys = array_merge([substr($name, 0, $position)], $matches[1]);
        $current = &$target;
        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }

    private function countJobs(): int
    {
        return (int)$this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT COUNT(uid) FROM tx_academicjobs_domain_model_job')
            ->fetchOne();
    }

    /**
     * A submission the validation set refuses comes back as the form, with the
     * summary above it and the refused fields marked - not as a `TypeError` and not
     * as a redirect.
     */
    #[Test]
    public function anInvalidSubmissionRendersTheFormWithItsErrors(): void
    {
        $this->setUpTestCase();

        // `title` and `companyName` are required and are left out; everything else
        // the validation set demands is supplied, so the two are the whole reason
        // for the refusal.
        $response = $this->submitJobForm([
            'title' => '',
            'description' => 'A job without a title',
            'companyName' => '',
            'employmentStartDate' => '2026-08-01',
            'employmentType' => '1',
            'type' => '1',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $content = (string)$response->getBody();
        $this->assertStringNotContainsString('TypeError', $content);
        $this->assertStringContainsString(
            'One or more fields have not been filled out correctly.',
            $content,
            'The summary of the refused submission is missing.',
        );
        // The one job of the fixture and nothing more.
        $this->assertSame(1, $this->countJobs(), 'A refused submission wrote a job.');

        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        // The form is rendered again, with the values that were submitted.
        $titles = $xpath->query('//input[@id="job.title"]');
        $this->assertNotFalse($titles);
        $this->assertSame(1, $titles->length, 'The refused submission did not render the form again.');
        // Every refused field is marked, and a field nobody complained about is not.
        foreach (['title', 'companyName'] as $refused) {
            $this->assertSame(
                1,
                $this->invalidWrapperCount($xpath, $refused),
                sprintf('The refused field "%s" is not marked.', $refused),
            );
        }
        $this->assertSame(
            0,
            $this->invalidWrapperCount($xpath, 'description'),
            'A field nobody complained about is marked as invalid.',
        );
    }

    /**
     * The wrapper of a field carries `is-invalid` exactly while that field has an
     * error of its own, which is the per-field half of the same `f:form.validationResults`.
     */
    private function invalidWrapperCount(\DOMXPath $xpath, string $identifier): int
    {
        $wrappers = $xpath->query(sprintf(
            '//label[@for="job.%s"]/parent::div[contains(concat(" ", normalize-space(@class), " "), " is-invalid ")]',
            $identifier,
        ));
        $this->assertNotFalse($wrappers);

        return $wrappers->length;
    }
}
