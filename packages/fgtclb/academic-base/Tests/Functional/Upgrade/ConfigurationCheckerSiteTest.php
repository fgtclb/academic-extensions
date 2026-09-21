<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Upgrade;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\AcademicBase\Upgrade\ConfigurationChecker;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFinding;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFindingKind;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The three checks a site configuration carries: a dependency on an alias set,
 * one extension delivered through a set and through a static template at the
 * same time, and the page TSconfig file next to the site configuration.
 *
 * The fixture extension `academic_test_configuration` ships the set
 * `fgtclb/academic-test-configuration` and the static template folder
 * `Configuration/TypoScript/Live`, which is the pair a site can hold both of.
 */
final class ConfigurationCheckerSiteTest extends AbstractAcademicBaseTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/academic-test-configuration',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ConfigurationCheck/pages.csv');
    }

    protected function tearDown(): void
    {
        // A written site configuration outlives the test instance, so the next
        // test would find a site it did not write.
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * The two alias sets are named by the checker rather than marked in the set
     * definitions, so the set does not have to exist for the notice to be
     * right: `Site::getSets()` answers the dependencies a site *declares*, and
     * a site declaring a set that 4.0 dropped is exactly the case this notice
     * is about.
     */
    #[Test]
    public function aDependencyOnAnAliasSetIsANotice(): void
    {
        $this->writeSite('legacy', 1, ['fgtclb/academic-persons-default']);

        $findings = $this->findingsOfKind(ConfigurationFindingKind::AliasSet);

        $this->assertCount(1, $findings);
        $this->assertSame('site:legacy', $findings[0]->subject);
        $this->assertSame(ContextualFeedbackSeverity::NOTICE, $findings[0]->severity);
        $this->assertStringContainsString('Depend on "fgtclb/academic-persons" instead', $findings[0]->message);
        $this->assertFalse($findings[0]->isProblem(), 'The alias still delivers, so it must not fail a pipeline');
    }

    #[Test]
    public function aDependencyOnTheAggregateSetIsNotReported(): void
    {
        $this->writeSite('current', 1, ['fgtclb/academic-test-configuration']);

        $this->assertSame([], $this->findingsOfKind(ConfigurationFindingKind::AliasSet));
    }

    #[Test]
    public function aSetAndAStaticTemplateOfOneExtensionAreReported(): void
    {
        $this->insertTypoScriptRecord(1, 'EXT:academic_test_configuration/Configuration/TypoScript/Live');
        $this->writeSite('both', 1, ['fgtclb/academic-test-configuration']);

        $findings = $this->findingsOfKind(ConfigurationFindingKind::SetAndStaticTemplate);

        $this->assertCount(1, $findings);
        $this->assertSame('site:both', $findings[0]->subject);
        $this->assertSame(ContextualFeedbackSeverity::WARNING, $findings[0]->severity);
        $this->assertStringContainsString(
            'delivers "academic_test_configuration" through a site set and through a static template',
            $findings[0]->message,
        );
        $this->assertStringContainsString('on its root page 1', $findings[0]->message);
    }

    /**
     * Both halves of the rule, so the assertion above cannot pass for a check
     * that reports any site with a set: a static template of *another*
     * extension is the shape the documentation asks for, and a record below
     * the root page is not the double parse the warning is about.
     */
    #[Test]
    public function aStaticTemplateOfAnotherExtensionOrBelowTheRootPageIsNotReported(): void
    {
        $this->insertTypoScriptRecord(1, 'EXT:academic_gone/Configuration/TypoScript/List');
        $this->insertTypoScriptRecord(2, 'EXT:academic_test_configuration/Configuration/TypoScript/Live');
        $this->writeSite('sound', 1, ['fgtclb/academic-test-configuration']);

        $this->assertSame([], $this->findingsOfKind(ConfigurationFindingKind::SetAndStaticTemplate));
    }

    /**
     * The third place page TSconfig is stored: a `page.tsconfig` file next to
     * the site configuration, which TYPO3 v13 and v14 read alike
     * (`TsConfigTreeBuilder::getSitePageTsConfigTree()`).
     */
    #[Test]
    public function aDeadImportInTheSitePageTsConfigIsReported(): void
    {
        $this->writeSite('imports', 1, []);
        $this->writeSitePageTsConfig(
            'imports',
            "@import 'EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig'\n"
            . "@import 'EXT:academic_test_configuration/Configuration/TSconfig/Live.tsconfig'\n",
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigImport);

        $this->assertSame(
            ['pages:2', 'pages:3', 'pages:5', 'site:imports'],
            array_map(
                static fn(ConfigurationFinding $finding): string => $finding->subject,
                $findings,
            ),
        );
        $this->assertStringContainsString(
            'The file "config/sites/imports/page.tsconfig" imports '
            . '"EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig"',
            $findings[3]->message,
        );
    }

    /**
     * @param non-empty-string $identifier
     * @param list<string> $sets
     */
    private function writeSite(string $identifier, int $rootPageId, array $sets): void
    {
        $site = $this->buildSiteConfiguration(rootPageId: $rootPageId, base: 'https://www.acme.test/');
        if ($sets !== []) {
            $site['dependencies'] = $sets;
        }
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $site,
            languages: [$this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/')],
        );
    }

    /**
     * Written after the site configuration, because writing that one removes
     * the whole folder first and flushes the site cache afterwards.
     */
    private function writeSitePageTsConfig(string $identifier, string $pageTsConfig): void
    {
        GeneralUtility::writeFile(
            $this->instancePath . '/typo3conf/sites/' . $identifier . '/page.tsconfig',
            $pageTsConfig,
            true,
        );
    }

    private function insertTypoScriptRecord(int $pageId, string $includeStaticFile): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => $pageId,
            'root' => $pageId === 1 ? 1 : 0,
            'clear' => 0,
            'title' => 'Probe',
            'constants' => '',
            'config' => '',
            'include_static_file' => $includeStaticFile,
        ]);
    }

    /**
     * @return list<ConfigurationFinding>
     */
    private function findingsOfKind(ConfigurationFindingKind $kind): array
    {
        return array_values(array_filter(
            $this->get(ConfigurationChecker::class)->check(),
            static fn(ConfigurationFinding $finding): bool => $finding->kind === $kind,
        ));
    }
}
