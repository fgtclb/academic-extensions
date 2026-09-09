<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academicjobs_newjobform` plugin with an existing job bound to the form
 * and pins the value the date controls are prefilled with.
 *
 * The form renders three date fields - `employmentStartDate`, `starttime` and `endtime` -
 * through one `Job/Forms/DateTime` partial, so the prefill format is spelled once and one
 * of the three is enough to pin it. They are native `<input type="date">` controls, and a
 * browser discards a value of such a control that is not an ISO `yyyy-mm-dd` date -
 * silently, without a parse error and without a hint in the markup. The partial used to
 * prefill them with `d.m.Y`, so every stored date arrived at the visitor as an empty
 * control and submitting the untouched form cleared the field. The format the control is
 * prefilled with is therefore the format `JobController::initializeCreateAction()`
 * configures the `DateTimeConverter` with, and this test is what keeps the two together.
 *
 * `JobController::newAction()` assigns no form object, so the job is bound to the form by
 * the `test_jobs_form_prefill` fixture extension through
 * `ModifyJobControllerNewActionViewEvent` - the documented place for an integrator to
 * offer a stored record for editing.
 */
final class AcademicJobsNewJobFormDatePrefillTest extends AbstractAcademicJobsTestCase
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

    private function fetchDateControl(string $content, string $identifier): \DOMElement
    {
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $inputs = (new \DOMXPath($document))->query(sprintf('//input[@id="job.%s"]', $identifier));
        $this->assertNotFalse($inputs);
        $this->assertSame(1, $inputs->length, sprintf('Missing input for "%s".', $identifier));
        $control = $inputs->item(0);
        $this->assertInstanceOf(\DOMElement::class, $control);

        return $control;
    }

    #[Test]
    public function dateControlOfAStoredJobIsPrefilledWithTheDateTheControlAccepts(): void
    {
        $this->setUpTestCase();

        $control = $this->fetchDateControl(
            $this->renderFrontendPage('https://www.acme.com/home'),
            'employmentStartDate',
        );

        $this->assertSame('date', $control->getAttribute('type'));
        $this->assertSame('2026-10-01', $control->getAttribute('value'));
    }

    /**
     * `data-render="datepicker"` was read by nothing in this repository - no JavaScript
     * module, no stylesheet and no template. It described a widget that does not exist
     * and that a native date control does not need.
     */
    #[Test]
    public function dateControlCarriesNoDatepickerAttribute(): void
    {
        $this->setUpTestCase();

        $control = $this->fetchDateControl(
            $this->renderFrontendPage('https://www.acme.com/home'),
            'employmentStartDate',
        );

        $this->assertFalse($control->hasAttribute('data-render'));
    }
}
