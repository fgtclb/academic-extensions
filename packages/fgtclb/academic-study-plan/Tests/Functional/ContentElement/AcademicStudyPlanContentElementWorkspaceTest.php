<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\ContentElement;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * What the `academic_study_plan` content element renders when workspace versions of its
 * records exist (ACE-476).
 *
 * Before that issue `StudyPlanService` built its three queries without any
 * `WorkspaceRestriction` and never overlaid a fetched row, with two consequences this
 * class exists to prevent from returning: the live frontend served every unpublished
 * draft to the public, and a workspace preview showed the live and the workspace state
 * side by side instead of the workspace state.
 *
 * The fixture holds, for one live study plan: a workspace version of the semester, of
 * the module and of the category, plus a semester that exists only in workspace 1.
 */
final class AcademicStudyPlanContentElementWorkspaceTest extends AbstractAcademicStudyPlanTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content', 'typo3/cms-workspaces');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicStudyPlanContentElementWorkspace/workspaceStudyPlan.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_study_plan/Configuration/TypoScript/ContentElement/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript',
                    'EXT:academic_study_plan/Tests/Functional/ContentElement/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function renderLive(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    private function renderInWorkspace(): string
    {
        return $this->renderFrontendPage(
            'https://www.acme.com/home',
            (new InternalRequestContext())->withBackendUserId(1)->withWorkspaceId(1),
        );
    }

    /**
     * Every record label the fixture can produce, in the order they sort, so an
     * assertion states presence and absence at once and a failure prints three lines
     * rather than the whole page.
     *
     * @return list<string>
     */
    private function renderedRecordLabels(string $content): array
    {
        preg_match_all('#(?:Live|Draft|Workspace Only) (?:Semester|Module|Category)#', $content, $matches);
        $labels = array_values(array_unique($matches[0]));
        sort($labels);

        return $labels;
    }

    /**
     * The live frontend shows the live state and nothing else.
     *
     * Without a condition on "t3ver_wsid" every workspace version is an ordinary row to
     * these queries, so unpublished drafts were served to the public next to the records
     * they are drafts of. "WorkspaceRestriction" is what excludes them: in the live
     * workspace it constrains to "t3ver_wsid = 0".
     */
    #[Test]
    public function liveFrontendRendersOnlyLiveRecords(): void
    {
        $this->assertSame(
            ['Live Category', 'Live Module', 'Live Semester'],
            $this->renderedRecordLabels($this->renderLive()),
        );
    }

    /**
     * A record created in a workspace and never published is not part of the live site.
     * It carries "t3ver_wsid = 1" like any other workspace row and is excluded by the
     * same constraint - what makes it worth its own test is that it has no live
     * counterpart at all, so no overlay would drop it and nothing else would hide it.
     */
    #[Test]
    public function liveFrontendDoesNotRenderRecordsThatExistOnlyInAWorkspace(): void
    {
        $this->assertNotContains('Workspace Only Semester', $this->renderedRecordLabels($this->renderLive()));
    }

    /**
     * The other direction: a workspace preview shows the workspace state.
     *
     * "WorkspaceRestriction" selects the live rows plus the workspace rows that have no
     * live counterpart, and deliberately not the plain versions - those come in through
     * "versionOL()", which replaces a live row with its draft while keeping the live
     * uid. So the live labels are not merely hidden here, they are overlaid.
     */
    #[Test]
    public function workspacePreviewRendersTheWorkspaceState(): void
    {
        $this->assertSame(
            ['Draft Category', 'Draft Module', 'Draft Semester', 'Workspace Only Semester'],
            $this->renderedRecordLabels($this->renderInWorkspace()),
        );
    }

    /**
     * Whether the content element is reachable at all in a preview was the open question
     * of ACE-476: the relations are keyed on live uids, and the workspace overlay of
     * "tt_content" happens in the core rendering above this extension. This pins the
     * answer instead of reasoning about it.
     */
    #[Test]
    public function workspacePreviewRendersTheContentElementAtAll(): void
    {
        $content = $this->renderInWorkspace();

        $this->assertStringContainsString('academic-study-plan', $content);
        $this->assertStringContainsString('data-study-plan="1"', $content);
    }
}
