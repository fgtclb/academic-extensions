<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Command;

use FGTCLB\AcademicBase\Command\UpgradeCheckCommand;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The configuration group on the command line.
 *
 * It takes no argument and reads the whole installation, so it runs on every
 * invocation - including one that names an extension for the template override
 * group. A pipeline that runs the command for one extension cannot miss the
 * configuration findings that way.
 */
final class UpgradeCheckCommandConfigurationTest extends AbstractAcademicBaseTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        // Ships the alias set the notice below is about. Without it the site
        // would depend on a set TYPO3 cannot provide, which is an error.
        'fgtclb/academic-study-plan',
        'tests/academic-test-configuration',
        'tests/test-upgrade-check-shared',
        'tests/test-upgrade-check',
        'tests/test-upgrade-check-project',
    ];

    private const FIXTURES = __DIR__ . '/../Upgrade/Fixtures/ConfigurationCheck/';

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function aRunWithoutAnyArgumentChecksTheConfigurationOfTheInstallation(): void
    {
        $tester = $this->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Configuration', $output);
        $this->assertStringContainsString('nothing to report', $output);
        $this->assertStringNotContainsString('Template overrides', $output);
    }

    #[Test]
    public function aConfigurationProblemIsListedAndFailsTheRun(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $tester = $this->execute([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('! static-template          sys_template:1', $output);
        $this->assertStringContainsString('holds no TypoScript in the installed version', $output);
        $this->assertStringContainsString('3 problems and 0 notices in the stored configuration.', $output);
    }

    /**
     * A notice describes configuration that still works, so it must not gate a
     * pipeline. The alias set is the only finding here.
     */
    #[Test]
    public function aNoticeAloneLetsTheRunSucceed(): void
    {
        $this->writeSiteWithAliasSet();

        $tester = $this->execute([]);

        $this->assertStringContainsString('i alias-set                site:legacy', $tester->getDisplay());
        $this->assertStringNotContainsString('unavailable-set', $tester->getDisplay());
        $this->assertStringContainsString(
            '0 problems and 1 notice in the stored configuration.',
            $tester->getDisplay(),
        );
        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * And next to problems it is listed, not swallowed - the summary line
     * counts both kinds.
     */
    #[Test]
    public function aNoticeIsListedNextToTheProblems(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'pages.csv');
        $this->writeSiteWithAliasSet();

        $tester = $this->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('i alias-set                site:legacy', $output);
        $this->assertStringContainsString('3 problems and 1 notice in the stored configuration.', $output);
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * A site on a set TYPO3 cannot provide answers every page with HTTP 500, so
     * the finding is an error and fails the run.
     */
    #[Test]
    public function anUnavailableSetFailsTheRun(): void
    {
        $this->writeSiteConfiguration(
            identifier: 'stale',
            site: [
                ...$this->buildSiteConfiguration(rootPageId: 1, base: 'https://www.acme.test/'),
                'dependencies' => ['fgtclb/academic-programs-content-load'],
            ],
            languages: [$this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/')],
        );

        $tester = $this->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('x unavailable-set          site:stale', $output);
        $this->assertStringContainsString('3.0 removed it', $output);
        $this->assertStringContainsString('1 problem and 0 notices in the stored configuration.', $output);
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    #[Test]
    public function theConfigurationGroupAlsoRunsForATemplateOverrideCheck(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $tester = $this->execute([
            'extension' => 'test_upgrade_check',
            '--override-path' => ['EXT:test_upgrade_check_project/Resources/Private/Extensions/TestUpgradeCheck/'],
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('3 problems and 0 notices in the stored configuration.', $output);
        $this->assertStringContainsString('2 problems and 2 notices in 1 override folder.', $output);
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * The exit status is the one of the whole run: a template override group
     * that found nothing but a notice must not turn a configuration problem
     * into a green pipeline.
     */
    #[Test]
    public function aConfigurationProblemFailsARunWhoseOverridesAreClean(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $tester = $this->execute([
            'extension' => 'test_upgrade_check',
            '--override-path' => ['EXT:test_upgrade_check_project/Resources/Private/Extensions/TestUpgradeCheck/Layouts/'],
            '--upstream-path' => 'EXT:test_upgrade_check/Resources/Private/Layouts/',
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('0 problems and 1 notice in 1 override folder.', $output);
        $this->assertStringContainsString('3 problems and 0 notices in the stored configuration.', $output);
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * Both options name the override folders of one extension. Taking them
     * without the extension key would compare them with nothing.
     */
    /**
     * @param string|list<string> $value
     */
    #[Test]
    #[DataProvider('optionsThatNeedTheExtensionKey')]
    public function anOptionWithoutAnExtensionIsInvalidInput(string $option, string|array $value): void
    {
        $tester = $this->execute([$option => $value]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('the extension key has to be given as well', $tester->getDisplay());
    }

    /**
     * Every option of the command belongs to the template override group.
     * Before the extension argument became optional, Symfony refused each of
     * these outright with "Not enough arguments"; accepting one now and
     * silently ignoring it would be worse than before.
     *
     * @return array<string, array{0: string, 1: string|list<string>}>
     */
    public static function optionsThatNeedTheExtensionKey(): array
    {
        return [
            'override path' => [
                '--override-path',
                ['EXT:test_upgrade_check_project/Resources/Private/Extensions/TestUpgradeCheck/'],
            ],
            'upstream path' => ['--upstream-path', 'EXT:test_upgrade_check/Resources/Private/Layouts/'],
            'site' => ['--site', 'probe'],
        ];
    }

    /**
     * `--upstream-path` names the folder the `--override-path` folders are
     * compared with, and nothing else - the folders a `--site` names are
     * compared with the upstream folder of their own kind. Accepting it
     * without one is accepting an option and ignoring it, which is the failure
     * mode this command exists to remove.
     *
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('upstreamPathWithoutAnOverrideFolder')]
    public function anUpstreamPathWithoutAnOverrideFolderIsInvalidInput(array $parameters): void
    {
        $tester = $this->execute([
            'extension' => 'test_upgrade_check',
            '--upstream-path' => 'EXT:test_upgrade_check/Resources/Private/Layouts/',
            ...$parameters,
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString(
            'so it needs at least one --override-path',
            $tester->getDisplay(),
        );
    }

    /**
     * @return array<string, array{0: array<string, string>}>
     */
    public static function upstreamPathWithoutAnOverrideFolder(): array
    {
        return [
            'on its own' => [[]],
            'with a site' => [['--site' => 'probe']],
        ];
    }

    private function writeSiteWithAliasSet(): void
    {
        $this->writeSiteConfiguration(
            identifier: 'legacy',
            site: [
                ...$this->buildSiteConfiguration(rootPageId: 1, base: 'https://www.acme.test/'),
                'dependencies' => ['fgtclb/academic-study-plan-default'],
            ],
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
