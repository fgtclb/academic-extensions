<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Plugins;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TESTS\TestPluginViewEvent\EventListener\LeftoverListProfilesListener;
use TESTS\TestPluginViewEvent\EventListener\RecordPluginViewListener;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * `ModifyPluginViewEvent` in every plugin of the academic extensions that renders an Extbase
 * view: once per rendering, with the plugin and action on its context, and with a variable
 * the listener assigns reaching the template.
 *
 * Every plugin sits on a page of its own, so a rendering is one plugin. The listener of
 * `EXT:test_plugin_view_event` records `<extension>/<plugin>/<action>` and assigns `probe`;
 * the templates of that extension print it for the persons list, the partner map and the job
 * form. The data are the least each action needs to take the path a row names: one profile
 * with one contract, one job, and nothing for the plugins that render an empty state.
 */
final class ModifyPluginViewEventTest extends AbstractAcademicBaseTestCase
{
    use FrontendPluginRenderingTrait {
        frontendPluginTestConfiguration as sharedFrontendPluginTestConfiguration;
    }
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const EXTENSIONS = [
        'academic_persons' => ['Default/constants.typoscript', 'Default/setup.typoscript'],
        'academic_jobs' => ['constants.typoscript', 'setup.typoscript'],
        'academic_partners' => ['constants.typoscript', 'setup.typoscript'],
        'academic_programs' => ['constants.typoscript', 'setup.typoscript'],
        'academic_projects' => ['constants.typoscript', 'setup.typoscript'],
        'academic_contacts4pages' => ['List/constants.typoscript', 'List/setup.typoscript'],
        'academic_bite_jobs' => ['List/constants.typoscript', 'List/setup.typoscript'],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content', 'typo3/cms-rte-ckeditor');
        $this->addTestExtensionsToLoad(
            'fgtclb/category-types',
            'fgtclb/academic-persons',
            'fgtclb/academic-jobs',
            'fgtclb/academic-partners',
            'fgtclb/academic-programs',
            'fgtclb/academic-projects',
            'fgtclb/academic-contacts4pages',
            'fgtclb/academic-bite-jobs',
            'tests/test-bitejobs-stub',
            'tests/test-plugin-view-event',
        );
        parent::setUp();
        RecordPluginViewListener::$renderings = [];
        RecordPluginViewListener::$assignedBefore = [];
        RecordPluginViewListener::$replaceValidations = false;
        RecordPluginViewListener::$replaceData = false;
        LeftoverListProfilesListener::$calls = 0;
    }

    protected function tearDown(): void
    {
        RecordPluginViewListener::$renderings = [];
        RecordPluginViewListener::$assignedBefore = [];
        RecordPluginViewListener::$replaceValidations = false;
        RecordPluginViewListener::$replaceData = false;
        LeftoverListProfilesListener::$calls = 0;
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The Extbase class schema cache stays in memory for this class, like in every other
     * test class that renders many plugins: TYPO3 core writes it from a destructor, and a
     * garbage collection inside another serialize() corrupts it (ACE-725, ACE-729, ACE-740).
     *
     * @param array<string, mixed> $additionalConfiguration
     * @return array<string, mixed>
     */
    protected function frontendPluginTestConfiguration(array $additionalConfiguration = []): array
    {
        return $this->sharedFrontendPluginTestConfiguration(array_replace_recursive([
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        'extbase' => [
                            'backend' => TransientMemoryBackend::class,
                        ],
                    ],
                ],
            ],
        ], $additionalConfiguration));
    }

    /**
     * @param list<string> $additionalSetupFiles
     */
    private function setUpTestCase(array $additionalSetupFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ModifyPluginViewEvent/allPlugins.csv');
        $constants = ['EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript'];
        $setup = ['EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript'];
        foreach (self::EXTENSIONS as $extensionKey => [$constantsFile, $setupFile]) {
            $constants[] = 'EXT:' . $extensionKey . '/Configuration/TypoScript/' . $constantsFile;
            $setup[] = 'EXT:' . $extensionKey . '/Configuration/TypoScript/' . $setupFile;
        }
        $setup[] = 'EXT:test_plugin_view_event/Configuration/TypoScript/Rendering.typoscript';
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => $constants,
                'setup' => array_merge($setup, $additionalSetupFiles),
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
     * A URL with plugin arguments, and the cHash the frontend asks for with them.
     *
     * @param array<string, array<string, int|string>> $arguments By plugin namespace.
     */
    private function pageUrl(int $pageId, string $slug, array $arguments = []): string
    {
        if ($arguments === []) {
            return 'https://www.acme.com/' . $slug;
        }
        $query = http_build_query($arguments);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=' . $pageId . '&' . $query);
        return 'https://www.acme.com/' . $slug . '?' . $query . '&cHash=' . $cacheHash;
    }

    /**
     * Every plugin and action that renders a view, and the three renderings of an empty or
     * not found state that return early. Where a row names a text, the page shows it, so the
     * row took the path it names: the job, the profile and the contract of the data set are
     * rendered on the main paths, and not on the early returns. The last column is a variable
     * the action assigns last on that path, so the listener finds it assigned.
     *
     * @return array<string, array{int, string, array<string, array<string, int|string>>, string, string, string, string}>
     */
    public static function renderings(): array
    {
        $listAndDetail = [
            'tx_academicpersons_listanddetail' => ['action' => 'detail', 'controller' => 'Profile', 'profile' => 1],
        ];
        return [
            'persons list' => [10, 'persons-list', [], 'AcademicPersons/List/list', '', '', 'defaultViewMode'],
            'persons list and detail, list' => [11, 'persons-list-and-detail', [], 'AcademicPersons/ListAndDetail/list', '', '', 'defaultViewMode'],
            'persons list and detail, detail' => [11, 'persons-list-and-detail', $listAndDetail, 'AcademicPersons/ListAndDetail/detail', 'Adams', '', 'publicProfile'],
            'persons detail' => [12, 'persons-detail', ['tx_academicpersons_detail' => ['profile' => 1]], 'AcademicPersons/Detail/detail', 'Adams', '', 'publicProfile'],
            'persons card' => [13, 'persons-card', [], 'AcademicPersons/Card/card', 'Adams', '', 'profiles'],
            'persons selected profiles' => [14, 'persons-selected-profiles', [], 'AcademicPersons/SelectedProfiles/selectedProfiles', 'Adams', '', 'profiles'],
            'persons selected profiles without a selection' => [15, 'persons-no-selected-profiles', [], 'AcademicPersons/SelectedProfiles/selectedProfiles', '', 'Adams', 'record'],
            'persons selected contracts' => [16, 'persons-selected-contracts', [], 'AcademicPersons/SelectedContracts/selectedContracts', 'Adams', '', 'contracts'],
            'persons selected contracts without a selection' => [17, 'persons-no-selected-contracts', [], 'AcademicPersons/SelectedContracts/selectedContracts', '', 'Adams', 'record'],
            'jobs list' => [20, 'jobs-list', [], 'AcademicJobs/List/list', '', '', 'jobs'],
            'jobs detail' => [21, 'jobs-detail', ['tx_academicjobs_detail' => ['job' => 1]], 'AcademicJobs/Detail/show', 'Research Assistant', 'No job advert could be found.', 'job'],
            'jobs detail without a job' => [21, 'jobs-detail', [], 'AcademicJobs/Detail/show', 'No job advert could be found.', 'Research Assistant', 'record'],
            'jobs new job form' => [22, 'jobs-new', [], 'AcademicJobs/NewJobForm/new', '', '', 'typeOptions'],
            'partners list' => [30, 'partners-list', [], 'AcademicPartners/List/list', '', '', 'filterTypes'],
            'partners map' => [31, 'partners-map', [], 'AcademicPartners/Map/map', '', '', 'filterTypes'],
            'partnerships list' => [32, 'partnerships-list', [], 'AcademicPartners/PartnershipsList/partnershipsList', '', '', 'partnershipRoles'],
            'partnerships teaser' => [33, 'partnerships-teaser', [], 'AcademicPartners/PartnershipsTeaser/partnershipsTeaser', '', '', 'partnershipRoles'],
            'programs list' => [40, 'programs-list', [], 'AcademicPrograms/ProgramList/list', '', '', 'filterTypes'],
            'programs finder' => [41, 'programs-finder', [], 'AcademicPrograms/ProgramFinder/finder', '', '', 'preselection'],
            'programs details' => [42, 'programs-details', [], 'AcademicPrograms/ProgramDetails/show', '', '', 'facts'],
            'projects list' => [50, 'projects-list', [], 'AcademicProjects/ProjectList/list', '', '', 'filterTypes'],
            'projects list of a single page' => [51, 'projects-list-single', [], 'AcademicProjects/ProjectListSingle/list', '', '', 'filterTypes'],
            'contacts for pages' => [60, 'contacts', [], 'AcademicContacts4pages/List/list', '', '', 'contactsWithoutRole'],
            'BITE jobs' => [70, 'bite-jobs', [], 'AcademicBiteJobs/List/list', '', '', 'jobs'],
        ];
    }

    /**
     * Exactly one dispatch per rendering, after the action's own assignments.
     *
     * @param array<string, array<string, int|string>> $arguments
     */
    #[DataProvider('renderings')]
    #[Test]
    public function everyRenderingDispatchesTheEventOnce(
        int $pageId,
        string $slug,
        array $arguments,
        string $expectedRendering,
        string $expectedContent,
        string $unexpectedContent,
        string $lastAssignedVariable,
    ): void {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage($this->pageUrl($pageId, $slug, $arguments));

        $this->assertSame([$expectedRendering], RecordPluginViewListener::$renderings);
        $this->assertCount(1, RecordPluginViewListener::$assignedBefore);
        $this->assertContains($lastAssignedVariable, RecordPluginViewListener::$assignedBefore[0]);
        $this->assertStringNotContainsString('Oops, an error occurred', $content);
        if ($expectedContent !== '') {
            $this->assertStringContainsString($expectedContent, $content);
        }
        if ($unexpectedContent !== '') {
            $this->assertStringNotContainsString($unexpectedContent, $content);
        }
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function probedTemplates(): array
    {
        return [
            'persons list' => [10, 'persons-list', 'AcademicPersons/List/list'],
            'partners map' => [31, 'partners-map', 'AcademicPartners/Map/map'],
            'jobs new job form' => [22, 'jobs-new', 'AcademicJobs/NewJobForm/new'],
        ];
    }

    #[DataProvider('probedTemplates')]
    #[Test]
    public function aVariableTheListenerAssignsReachesTheTemplate(int $pageId, string $slug, string $expectedRendering): void
    {
        $this->setUpTestCase(['EXT:test_plugin_view_event/Configuration/TypoScript/ProbeTemplates.typoscript']);

        $content = $this->renderFrontendPage($this->pageUrl($pageId, $slug));

        $this->assertStringContainsString('<p class="plugin-view-probe">probe of ' . $expectedRendering . '</p>', $content);
    }

    /**
     * The event comes after the action's own assignments, so a listener that assigns a
     * variable the action assigned replaces it in the view: `data` is the content element row
     * everywhere, and its header is the slug of the page.
     */
    #[DataProvider('probedTemplates')]
    #[Test]
    public function aListenerReplacesAVariableTheActionAssigned(int $pageId, string $slug, string $expectedRendering): void
    {
        $this->setUpTestCase(['EXT:test_plugin_view_event/Configuration/TypoScript/ProbeTemplates.typoscript']);
        RecordPluginViewListener::$replaceData = true;

        $content = $this->renderFrontendPage($this->pageUrl($pageId, $slug));

        $this->assertSame([$expectedRendering], RecordPluginViewListener::$renderings);
        $this->assertStringContainsString('<p class="plugin-view-header">header of the listener</p>', $content);
    }

    /**
     * The job form assigns its validations after the event, so a listener cannot replace them:
     * the title keeps its required marker and the contact e-mail address its input type.
     */
    #[Test]
    public function aListenerCannotReplaceTheValidationsOfTheJobForm(): void
    {
        $this->setUpTestCase();
        RecordPluginViewListener::$replaceValidations = true;

        $content = $this->renderFrontendPage($this->pageUrl(22, 'jobs-new'));

        $this->assertSame(['AcademicJobs/NewJobForm/new'], RecordPluginViewListener::$renderings);
        $this->assertMatchesRegularExpression('#<label class="form-label" for="job\.title">\s*[^<]*\s*<abbr title="required">\*</abbr>#', $content);
        $this->assertMatchesRegularExpression('#<input[^>]+type="email"[^>]+name="tx_academicjobs_newjobform\[job\]\[contactEmail\]"#', $content);
    }

    /**
     * A listener of a persons event that 3.0 removed is registered as before, and never called:
     * the container is built, and the list renders.
     */
    #[Test]
    public function aListenerOfTheRemovedPersonsListEventIsNotCalled(): void
    {
        $this->setUpTestCase();

        $this->renderFrontendPage($this->pageUrl(10, 'persons-list'));

        $this->assertSame(['AcademicPersons/List/list'], RecordPluginViewListener::$renderings);
        $this->assertSame(0, LeftoverListProfilesListener::$calls);
    }
}
