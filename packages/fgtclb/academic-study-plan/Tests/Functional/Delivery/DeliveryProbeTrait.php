<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Delivery;

use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The scaffolding both delivery test classes of this extension need.
 *
 * The site set half of the delivery only exists from TYPO3 v13.1 on, so it is tested in
 * `Tests/Functional/Core13/SiteSet/`, while the static template half is the only
 * mechanism TYPO3 v12 has and is tested for both core versions. That is two classes
 * around one probe, and the probe is what lives here.
 *
 * A class using this trait has to declare its own `LANGUAGE_PRESETS` - which languages a
 * test needs is part of what it tests, and a constant in a trait is a parse time fatal
 * on PHP 8.1, which this branch still supports.
 */
trait DeliveryProbeTrait
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    /**
     * The constant the probe renders, assigned by `constants.typoscript` of the component
     * and by nothing else.
     */
    protected function componentConstantMarkup(): string
    {
        return '<div id="constant">EXT:academic_study_plan/Resources/Private/Frontend/Default/Templates/</div>';
    }

    /**
     * A value the probe copies out of the setup of the component.
     */
    protected function componentSetupMarkup(): string
    {
        return '<div id="setup">AcademicStudyPlan</div>';
    }

    /**
     * The setup substitutes the constants into the Fluid root paths of the content
     * element. An unsubstituted constant is not an error either - it renders as the
     * literal `{$…}` - so it is asserted with its resolved value.
     */
    protected function componentSubstitutedMarkup(): string
    {
        return '<div id="substituted">EXT:academic_study_plan/Resources/Private/Frontend/Default/Partials/</div>';
    }

    /**
     * The stylesheet of the content element. On this branch the frontend assets are still
     * loaded through `page.includeCSS` and `page.includeJSFooter`, because the Fluid view
     * helper that loads a module does not exist on TYPO3 v12 - so they are part of the
     * TypoScript a component has to deliver, not of its template.
     */
    protected function componentStylesheetMarkup(): string
    {
        return '<div id="css">EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css</div>';
    }

    /**
     * The site identifier is derived from what the site is configured with, and that is
     * not cosmetic. `TsConfigTreeBuilder::getSitePageTsConfigTree()` caches the page
     * TSconfig a site's sets deliver under the site identifier alone, and the test
     * instance keeps that cache for the whole class. Reusing one identifier for
     * differently configured sites therefore answers the second test with the result of
     * the first - which looks exactly like a set that delivers too much.
     *
     * @param list<string> $dependencies Site sets the site configuration names. Ignored by
     *        TYPO3 v12, which has no site set API at all.
     * @param string $includeStaticFile Static template the `sys_template` record selects.
     * @param string $pageTsConfigInclude Page TSconfig file the page record selects, the
     *        only way to reach a component page TSconfig without site sets.
     */
    protected function setUpSite(
        array $dependencies = [],
        string $includeStaticFile = '',
        string $pageTsConfigInclude = '',
    ): void {
        $identifier = 'acme-' . substr(
            md5(implode(',', $dependencies) . '|' . $includeStaticFile . '|' . $pageTsConfigInclude),
            0,
            10,
        );

        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        if ($pageTsConfigInclude !== '') {
            $this->getConnectionPool()->getConnectionForTable('pages')->update(
                'pages',
                ['tsconfig_includes' => $pageTsConfigInclude],
                ['uid' => 1],
            );
        }
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                // Not "3": a clear flag discards everything the site sets contributed.
                'clear' => 0,
                'title' => 'Probe',
                'constants' => '',
                'config' => '@import \'EXT:academic_study_plan/Tests/Functional/Delivery/Fixtures/Probe.typoscript\'',
                'include_static_file' => $includeStaticFile,
            ],
        );
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: $this->frontendPluginTestBase(),
                additionalRootConfiguration: $dependencies === [] ? [] : ['dependencies' => $dependencies],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    /**
     * @param array<string, mixed> $pageTsConfig
     * @return list<string>
     */
    protected function removedContentElementTypes(array $pageTsConfig): array
    {
        return GeneralUtility::trimExplode(
            ',',
            (string)($pageTsConfig['TCEFORM.']['tt_content.']['CType.']['removeItems'] ?? ''),
            true,
        );
    }
}
