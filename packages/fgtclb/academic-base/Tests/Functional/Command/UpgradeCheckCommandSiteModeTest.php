<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Command;

use FGTCLB\AcademicBase\Command\UpgradeCheckCommand;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `--site` takes the override folders from the rendered TypoScript of a site
 * instead of from the command line, which is the step the command exists to
 * remove: reading the view root paths of every plugin by hand.
 *
 * The fixture TypoScript carries the two shapes that make the naive rule "every
 * root path except the extension's own is a project override" wrong. The
 * partial root paths of the academic plugins hold the shared partials of
 * `academic_base`, and those of `academic_persons_edit` hold the partials of
 * `fluid_styled_content`. Neither is a project override, and reporting either
 * would bury the findings that matter under folders nobody in the project
 * wrote.
 */
final class UpgradeCheckCommandSiteModeTest extends AbstractAcademicBaseTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-fluid-styled-content',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/test-upgrade-check-shared',
        'tests/test-upgrade-check',
        'tests/test-upgrade-check-project',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/UpgradeCheck/pages.csv');
    }

    protected function tearDown(): void
    {
        // A written site configuration outlives the test instance, so the next
        // test would find a site it did not write.
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * All three kinds of root path are followed, each compared with the upstream
     * folder of its own kind - which is why `--upstream-path` is not needed in
     * this mode.
     */
    #[Test]
    public function theProjectRootPathsOfAllThreeKindsAreChecked(): void
    {
        $this->setUpSiteWithTypoScript();

        $tester = $this->execute(['extension' => 'test_upgrade_check', '--site' => 'probe']);

        $output = $tester->getDisplay();
        $this->assertStringContainsString(
            'plugin.tx_testupgradecheck.view.templateRootPaths.10',
            $output,
            'The project template root path is checked',
        );
        $this->assertStringContainsString(
            'plugin.tx_testupgradecheck.view.partialRootPaths.30',
            $output,
            'The project partial root path is checked',
        );
        $this->assertStringContainsString(
            'plugin.tx_testupgradecheck.view.layoutRootPaths.10',
            $output,
            'The project layout root path is checked',
        );
        $this->assertStringContainsString('x missing-upstream  Profile/Show.html', $output);
        $this->assertStringContainsString('= identical         Profile/List.html', $output);
        $this->assertStringContainsString('! case-mismatch     Profile/image.html -> Profile/Image.html', $output);
        $this->assertStringContainsString('2 problems and 2 notices in 3 override folders.', $output);
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * The extension's own folders are not overrides of themselves, and neither
     * are the folders of an extension it depends on or of a system extension.
     * Without the exclusion the run reports the whole `Partials/` tree of
     * `academic_base` and of `fluid_styled_content` as dead.
     */
    #[Test]
    public function theFoldersShippedByTheExtensionsThemselvesAreNotReported(): void
    {
        $this->setUpSiteWithTypoScript();

        $output = $this->execute(['extension' => 'test_upgrade_check', '--site' => 'probe'])->getDisplay();

        $this->assertStringNotContainsString(
            'partialRootPaths.-1)',
            $output,
            'The partials of the package the extension requires are shipped, not overridden',
        );
        $this->assertStringNotContainsString(
            'partialRootPaths.-2)',
            $output,
            'academic_base is reached only through test_upgrade_check_shared: the requirement '
            . 'closure is walked to its end, not one level deep',
        );
        $this->assertStringNotContainsString(
            'partialRootPaths.0)',
            $output,
            'The extension checks nothing against its own folder',
        );
        $this->assertStringNotContainsString(
            'partialRootPaths.20)',
            $output,
            'The partials of a system extension are shipped, not overridden',
        );
        $this->assertStringNotContainsString('templateRootPaths.0)', $output);
        $this->assertStringNotContainsString('layoutRootPaths.0)', $output);
        $this->assertStringNotContainsString('Academic/Image.html', $output);
        $this->assertStringNotContainsString('Media/Gallery.html', $output);
        // Without this the four assertions above also hold for a run that
        // reported nothing at all.
        $this->assertStringContainsString('in 3 override folders.', $output);
    }

    /**
     * A site and explicit folders in one run: the command is meant to be a
     * single invocation in a pipeline.
     */
    #[Test]
    public function aSiteCombinesWithAnExplicitOverrideFolder(): void
    {
        $this->setUpSiteWithTypoScript();

        $output = $this->execute([
            'extension' => 'test_upgrade_check',
            '--site' => 'probe',
            '--override-path' => ['EXT:test_upgrade_check_project/Resources/Private/Extensions/TestUpgradeCheck/'],
        ])->getDisplay();

        $this->assertStringContainsString('4 override folders.', $output);
        $this->assertStringContainsString(
            'EXT:test_upgrade_check_project/Resources/Private/Extensions/TestUpgradeCheck/' . "\n",
            $output,
            'The explicitly named folder is checked next to the three of the site',
        );
    }

    /**
     * `academic_base` ships nothing below `Resources/Private/` but `Partials/`
     * and `Language/`, so the upstream folder a template root path is compared
     * with is not there at all. Every file of the project's folder is a dead
     * override then, and that is the answer - not an error about the extension.
     */
    #[Test]
    public function aKindTheExtensionShipsNoFolderForMakesEveryOverrideDead(): void
    {
        $this->setUpSiteWithTypoScript();

        $tester = $this->execute(['extension' => 'academic_base', '--site' => 'probe']);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('compared with EXT:academic_base/Resources/Private/Templates/', $output);
        $this->assertStringContainsString('x missing-upstream  Profile/List.html', $output);
        $this->assertStringContainsString('x missing-upstream  Profile/Show.html', $output);
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    #[Test]
    public function anUnknownSiteIsInvalidInput(): void
    {
        $this->setUpSiteWithTypoScript();

        $tester = $this->execute(['extension' => 'test_upgrade_check', '--site' => 'no-such-site']);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('There is no site "no-such-site".', $tester->getDisplay());
    }

    /**
     * A site that renders TypoScript but nothing below
     * `plugin.tx_testupgradecheck`. Reporting nothing and succeeding would tell
     * a pipeline the project is clean, when in fact the site was never asked
     * about this extension.
     *
     * The site needs a root TypoScript record for this: without one, TYPO3 v14
     * refuses to build the frontend environment at all ("No site configuration
     * or TypoScript template record found!"), while TYPO3 v13 builds it and
     * renders the core default TypoScript. Both end in `INVALID`, but only a
     * site with a record reaches this branch on both versions.
     */
    #[Test]
    public function aSiteConfiguringNoRootPathForTheExtensionIsInvalidInput(): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 2,
                'root' => 1,
                'clear' => 3,
                'title' => 'Unrelated',
                'constants' => '',
                'config' => 'page = PAGE',
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'bare',
            site: $this->buildSiteConfiguration(rootPageId: 2, base: 'https://www.acme.test/'),
            languages: [$this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/')],
        );

        $tester = $this->execute(['extension' => 'test_upgrade_check', '--site' => 'bare']);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString(
            'configures no view root path below "plugin.tx_testupgradecheck.view"',
            $tester->getDisplay(),
        );
    }

    /**
     * The frontend environment of a site whose root page is not there cannot be
     * built at all. The command names the site rather than letting the exception
     * of the state manager reach the console.
     */
    #[Test]
    public function aSiteWhoseFrontendEnvironmentCannotBeBuiltIsInvalidInput(): void
    {
        $this->writeSiteConfiguration(
            identifier: 'broken',
            site: $this->buildSiteConfiguration(rootPageId: 99, base: 'https://www.acme.invalid/'),
            languages: [$this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/')],
        );

        $tester = $this->execute(['extension' => 'test_upgrade_check', '--site' => 'broken']);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString(
            'The frontend environment of the site "broken" could not be built',
            $tester->getDisplay(),
        );
    }

    /**
     * A root path naming a folder that is not there is a defect of the site's
     * own configuration, so the run says which TypoScript path named it and
     * does not pretend to have checked everything.
     */
    #[Test]
    public function aRootPathNamingAMissingFolderIsInvalidInput(): void
    {
        $this->setUpSiteWithTypoScript(
            "plugin.tx_testupgradecheck.view.templateRootPaths.20 = EXT:test_upgrade_check_project/Resources/Private/Gone/\n",
        );

        $tester = $this->execute(['extension' => 'test_upgrade_check', '--site' => 'probe']);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString(
            'plugin.tx_testupgradecheck.view.templateRootPaths.20 names the folder '
            . '"EXT:test_upgrade_check_project/Resources/Private/Gone/", which does not exist.',
            $output,
        );
        $this->assertStringContainsString(
            'x missing-upstream  Profile/Show.html',
            $output,
            'One broken root path does not swallow the findings of the sound ones',
        );
        $this->assertStringContainsString('in 3 override folders.', $output);
    }

    /**
     * An absolute path outside the project root is refused by
     * `GeneralUtility::getFileAbsFileName()`, so "does not exist" would send the
     * integrator looking for a folder that is plainly there.
     */
    #[Test]
    public function aRootPathOutsideTheProjectRootSaysSo(): void
    {
        $this->setUpSiteWithTypoScript(
            "plugin.tx_testupgradecheck.view.templateRootPaths.20 = /etc/typo3-templates/\n",
        );

        $tester = $this->execute(['extension' => 'test_upgrade_check', '--site' => 'probe']);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('cannot be resolved', $tester->getDisplay());
    }

    /**
     * A site that cannot be read invalidates the run - but the folders the
     * command line named were checked, and their findings have to survive it.
     */
    #[Test]
    public function aFailingSiteKeepsTheFindingsOfAnExplicitOverrideFolder(): void
    {
        $tester = $this->execute([
            'extension' => 'test_upgrade_check',
            '--site' => 'no-such-site',
            '--override-path' => ['EXT:test_upgrade_check_project/Resources/Private/Extensions/TestUpgradeCheck/'],
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('There is no site "no-such-site".', $output);
        $this->assertStringContainsString('x missing-upstream  Templates/Profile/Show.html', $output);
    }

    /**
     * The command builds a frontend environment for the site and has to leave
     * the process as it found it - a check that runs before a second command in
     * the same process must not inherit its request.
     */
    #[Test]
    public function theGlobalRequestIsRestoredAfterTheRun(): void
    {
        $this->setUpSiteWithTypoScript();
        // A sentinel, because in a functional test there is no request to begin
        // with: comparing null with null would pass without anything being
        // restored at all.
        $requestBefore = new ServerRequest('https://www.acme.com/sentinel');
        $GLOBALS['TYPO3_REQUEST'] = $requestBefore;
        $contextBefore = GeneralUtility::makeInstance(Context::class)->getAspect('date');

        $this->execute(['extension' => 'test_upgrade_check', '--site' => 'probe']);

        $this->assertSame($requestBefore, $GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertSame($contextBefore, GeneralUtility::makeInstance(Context::class)->getAspect('date'));
        unset($GLOBALS['TYPO3_REQUEST']);
    }

    private function setUpSiteWithTypoScript(string $additionalSetup = ''): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 3,
                'title' => 'Probe',
                'constants' => '',
                'config' => "@import 'EXT:test_upgrade_check/Configuration/TypoScript/setup.typoscript'\n" . $additionalSetup,
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'probe',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: 'https://www.acme.com/'),
            languages: [$this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/')],
        );
    }

    /**
     * @param array<string, string|list<string>> $parameters
     */
    private function execute(array $parameters): CommandTester
    {
        $tester = new CommandTester($this->get(UpgradeCheckCommand::class));
        $tester->execute($parameters);

        return $tester;
    }
}
