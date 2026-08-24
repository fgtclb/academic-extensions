<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Core13\SiteSet;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\AcademicStudyPlan\Tests\Functional\SiteSet\DeliveryProbeTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 * TYPO3 v12 has no site set API at all, which is why this class sits in a `Core13`
 * folder rather than only carrying the group attribute: `SetRegistry` and `SetDefinition`
 * do not exist there, and the group excludes the class from PHPUnit but not from PHPStan,
 * which analyses the sources against the installed core. The static template half of the
 * same delivery is tested for both core versions in
 * `Tests/Functional/SiteSet/StaticTemplateDeliveryTest.php`.
 */
#[Group('not-core-12')]
final class SiteSetDeliveryTest extends AbstractAcademicStudyPlanTestCase
{
    use DeliveryProbeTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const CONTENT_ELEMENT_TYPE = 'academic_study_plan';

    private const COMPONENT_SET = 'fgtclb/academic-study-plan-content-element';
    private const AGGREGATE_SET = 'fgtclb/academic-study-plan';
    private const COMPATIBILITY_SET = 'fgtclb/academic-study-plan-default';

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

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            $this->componentConstantMarkup(),
            $body,
            sprintf('The set "%s" did not deliver "constants.typoscript" of the component.', $set),
        );
        $this->assertStringContainsString(
            $this->componentSetupMarkup(),
            $body,
            sprintf('The set "%s" did not deliver "setup.typoscript" of the component.', $set),
        );
        $this->assertStringContainsString(
            $this->componentSubstitutedMarkup(),
            $body,
            sprintf('The set "%s" delivered a setup the constants were not substituted into.', $set),
        );
        $this->assertStringContainsString(
            $this->componentStylesheetMarkup(),
            $body,
            sprintf('The set "%s" did not deliver the frontend assets of the content element.', $set),
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
}
