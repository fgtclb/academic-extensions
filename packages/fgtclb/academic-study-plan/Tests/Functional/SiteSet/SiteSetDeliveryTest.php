<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\SiteSet;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Proves that the site sets of this extension deliver what their `config.yaml` claims.
 *
 * Both keys of a set are strings that the core resolves at runtime, and both fail
 * silently when they are wrong: `SysTemplateTreeBuilder::handleSetInclude()` and
 * `TsConfigTreeBuilder::getSitePageTsConfigTree()` `file_exists()`-guard the files they
 * read and simply continue when one is missing. A typo in `typoscript:` or in `pagets:`
 * therefore produces no error anywhere, only a site that is configured differently than
 * the integrator expects - which is the whole reason this restructuring exists.
 *
 * This extension is the one that had something to lose. It hid its content element and
 * registered a page TSconfig file before this restructuring, through paths that all
 * moved, so these tests cover behaviour that already existed rather than behaviour that
 * is being introduced.
 *
 * The probe TypoScript renders one constant of the component, one value its setup
 * assigns and one value its setup substitutes the constant into, so a delivery that did
 * not happen shows up as a wrong value rather than as an exception. The `sys_template`
 * record it is imported from carries `clear = 0` on purpose: the backend button "Create
 * a root TypoScript record" writes `clear = 3`, which discards everything the site sets
 * contributed, and so does `FunctionalTestCase::setUpFrontendRootPage()`.
 */
final class SiteSetDeliveryTest extends AbstractAcademicStudyPlanTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const CONTENT_ELEMENT_TYPE = 'academic_study_plan';

    private const COMPONENT_SET = 'fgtclb/academic-study-plan-content-element';
    private const AGGREGATE_SET = 'fgtclb/academic-study-plan';
    private const COMPATIBILITY_SET = 'fgtclb/academic-study-plan-default';

    /**
     * The constant the probe renders, assigned by `constants.typoscript` of the component
     * and by nothing else.
     */
    private const COMPONENT_CONSTANT = '<div id="constant">EXT:academic_study_plan/Resources/Private/Frontend/Default/Templates/</div>';

    /**
     * A value the probe copies out of the setup of the component.
     */
    private const COMPONENT_SETUP = '<div id="setup">AcademicStudyPlan</div>';

    /**
     * The setup substitutes the constants into the Fluid root paths of the content
     * element. An unsubstituted constant is not an error either - it renders as the
     * literal `{$…}` - so it is asserted with its resolved value.
     */
    private const COMPONENT_SUBSTITUTED = '<div id="substituted">EXT:academic_study_plan/Resources/Private/Frontend/Default/Partials/</div>';

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function everythingDeliveringSetDataProvider(): \Generator
    {
        yield 'component' => [self::COMPONENT_SET];
        yield 'aggregate' => [self::AGGREGATE_SET];
        yield 'compatibility' => [self::COMPATIBILITY_SET];
    }

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The compatibility set is in the data provider because it is the name this extension
     * published, and because a dependency that does not resolve is silent: a site
     * configuration naming it would simply get nothing.
     */
    #[Test]
    #[DataProvider('everythingDeliveringSetDataProvider')]
    public function siteSetDeliversTheComponentTypoScript(string $set): void
    {
        $this->setUpSite(dependencies: [$set]);

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::COMPONENT_CONSTANT,
            $body,
            sprintf('The set "%s" did not deliver "constants.typoscript" of the component.', $set),
        );
        $this->assertStringContainsString(
            self::COMPONENT_SETUP,
            $body,
            sprintf('The set "%s" did not deliver "setup.typoscript" of the component.', $set),
        );
        $this->assertStringContainsString(
            self::COMPONENT_SUBSTITUTED,
            $body,
            sprintf('The set "%s" delivered a setup the constants were not substituted into.', $set),
        );
    }

    /**
     * The counterpart of the test above for an installation without site sets. It also
     * covers `Configuration/TypoScript/Full/include_static_file.txt`, whose entries are
     * comma separated and reach nothing at all when they are written any other way.
     */
    #[Test]
    public function aggregateStaticTemplateDeliversTheComponentTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_study_plan/Configuration/TypoScript/Full');

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::COMPONENT_CONSTANT,
            $body,
            'The aggregate static template did not deliver "constants.typoscript" of the component.',
        );
        $this->assertStringContainsString(
            self::COMPONENT_SETUP,
            $body,
            'The aggregate static template did not deliver "setup.typoscript" of the component.',
        );
        $this->assertStringContainsString(
            self::COMPONENT_SUBSTITUTED,
            $body,
            'The aggregate static template delivered a setup the constants were not substituted into.',
        );
    }

    /**
     * The other half of the delivery: the content element is hidden for the whole
     * installation, and naming a set in the site configuration is one of the two ways to
     * bring it back. No page carries a `tsconfig_includes` entry here, so the set is the
     * only thing that can do it.
     */
    #[Test]
    #[DataProvider('everythingDeliveringSetDataProvider')]
    public function siteSetDeliversTheComponentPageTsConfig(string $set): void
    {
        $this->setUpSite(dependencies: [$set]);

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);

        $this->assertNotContains(
            self::CONTENT_ELEMENT_TYPE,
            $this->removedContentElementTypes($pageTsConfig),
            sprintf('The set "%s" did not re-enable the content element.', $set),
        );
        $this->assertArrayHasKey(
            self::CONTENT_ELEMENT_TYPE . '.',
            $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [],
            sprintf('The set "%s" did not deliver the new content element wizard entry.', $set),
        );
    }

    /**
     * The hide half, asserted on its own. Without it the re-enable assertion above cannot
     * fail: it checks that the content element is absent from `removeItems`, and an empty
     * list satisfies that just as well as a correct one.
     */
    #[Test]
    public function theContentElementIsHiddenWithoutASiteSet(): void
    {
        $this->setUpSite();

        $this->assertContains(
            self::CONTENT_ELEMENT_TYPE,
            $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1)),
            'The content element is selectable although no set and no page TSconfig enable it.',
        );
    }

    /**
     * Pins the two strings the tests above depend on, and the files they point at.
     */
    #[Test]
    public function componentSetPointsAtTheFilesTheStaticRegistrationUses(): void
    {
        $component = $this->setRegistry()->getSet(self::COMPONENT_SET);

        $this->assertNotNull($component, sprintf('The set "%s" is not registered.', self::COMPONENT_SET));
        $this->assertSame(
            'EXT:academic_study_plan/Configuration/TypoScript/ContentElement/',
            $component->typoscript,
        );
        $this->assertSame(
            'EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig',
            $component->pagets,
        );
        $this->assertDirectoryExists(GeneralUtility::getFileAbsFileName((string)$component->typoscript));
        $this->assertFileExists(GeneralUtility::getFileAbsFileName((string)$component->pagets));
    }

    /**
     * The aggregate carries no payload of its own on purpose: it delivers through the
     * component set, and a `typoscript:` of its own would parse the same files twice.
     */
    #[Test]
    public function aggregateSetDependsOnTheComponentAndCarriesNoPayload(): void
    {
        $aggregate = $this->setRegistry()->getSet(self::AGGREGATE_SET);

        $this->assertNotNull($aggregate, sprintf('The set "%s" is not registered.', self::AGGREGATE_SET));
        $this->assertSame([self::COMPONENT_SET], $aggregate->dependencies);
        $this->assertSetCarriesNoPayload($aggregate);
    }

    /**
     * The name this extension published before the split. A site configuration that names
     * it must keep getting what it got, and a set that is not found is not an error - the
     * site simply gets nothing.
     */
    #[Test]
    public function compatibilitySetDelegatesToTheAggregate(): void
    {
        $compatibility = $this->setRegistry()->getSet(self::COMPATIBILITY_SET);

        $this->assertNotNull($compatibility, sprintf('The set "%s" is not registered.', self::COMPATIBILITY_SET));
        $this->assertSame([self::AGGREGATE_SET], $compatibility->dependencies);
        $this->assertSetCarriesNoPayload($compatibility);
    }

    private function setRegistry(): SetRegistry
    {
        $setRegistry = $this->get(SetRegistry::class);
        $this->assertInstanceOf(SetRegistry::class, $setRegistry);

        return $setRegistry;
    }

    /**
     * @param array<string, mixed> $pageTsConfig
     * @return list<string>
     */
    private function removedContentElementTypes(array $pageTsConfig): array
    {
        return GeneralUtility::trimExplode(
            ',',
            (string)($pageTsConfig['TCEFORM.']['tt_content.']['CType.']['removeItems'] ?? ''),
            true,
        );
    }

    /**
     * A set that declares neither key does not get `null`: the core defaults both to the
     * set folder itself (`YamlSetDefinitionProvider::createDefinition()`), and reads
     * whatever it finds there. "Carries no payload" therefore means the set folder holds
     * none of the four files the two mechanisms look for.
     */
    private function assertSetCarriesNoPayload(SetDefinition $set): void
    {
        $typoScriptPath = rtrim(GeneralUtility::getFileAbsFileName((string)$set->typoscript), '/') . '/';
        foreach (['constants.typoscript', 'setup.typoscript', 'include_static_file.txt'] as $fileName) {
            $this->assertFileDoesNotExist(
                $typoScriptPath . $fileName,
                sprintf('The set "%s" carries a payload of its own: %s', $set->name, $fileName),
            );
        }
        $this->assertFileDoesNotExist(
            GeneralUtility::getFileAbsFileName((string)$set->pagets),
            sprintf('The set "%s" carries a page TSconfig of its own.', $set->name),
        );
    }

    /**
     * The site identifier is derived from what the site is configured with, and that is
     * not cosmetic. `TsConfigTreeBuilder::getSitePageTsConfigTree()` caches the page
     * TSconfig a site's sets deliver under the site identifier alone, and the test
     * instance keeps that cache for the whole class. Reusing one identifier for
     * differently configured sites therefore answers the second test with the result of
     * the first - which looks exactly like a set that delivers too much.
     *
     * @param list<string> $dependencies Site sets the site configuration names.
     * @param string $includeStaticFile Static template the `sys_template` record selects.
     */
    private function setUpSite(array $dependencies = [], string $includeStaticFile = ''): void
    {
        $identifier = 'acme-' . substr(md5(implode(',', $dependencies) . '|' . $includeStaticFile), 0, 10);

        $this->importCSVDataSet(__DIR__ . '/Fixtures/SiteSetDelivery/pages.csv');
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                // Not "3": a clear flag discards everything the site sets contributed.
                'clear' => 0,
                'title' => 'Probe',
                'constants' => '',
                'config' => '@import \'EXT:academic_study_plan/Tests/Functional/SiteSet/Fixtures/TypoScript/Probe.typoscript\'',
                'include_static_file' => $includeStaticFile,
            ],
        );
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: $dependencies === [] ? [] : ['dependencies' => $dependencies],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
