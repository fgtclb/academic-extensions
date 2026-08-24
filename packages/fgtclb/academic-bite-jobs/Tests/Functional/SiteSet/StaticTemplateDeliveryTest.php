<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\SiteSet;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The half of the delivery contract that holds on both supported core versions.
 *
 * Site sets do not exist on TYPO3 v12 - they arrived in v13.1 (Feature: #103437) - so
 * on that version the static template and the auto-included
 * `Configuration/page.tsconfig` are the *only* mechanisms there are. The site-set half
 * of the same contract is asserted by `Core13\SiteSet\SiteSetDeliveryTest`, which runs
 * on v13 alone.
 *
 * The probe TypoScript renders one constant and one setup value of the component, so a
 * delivery that did not happen shows up as a wrong value rather than as an exception.
 * The `sys_template` record it is imported from carries `clear = 0` on purpose: the
 * backend button "Create a root TypoScript record" writes `clear = 3`, which discards
 * everything the site sets contributed, and so does
 * `FunctionalTestCase::setUpFrontendRootPage()`.
 */
final class StaticTemplateDeliveryTest extends AbstractDeliveryTestCase
{
    /**
     * Covers `Configuration/TypoScript/Full/include_static_file.txt`, whose entries are
     * comma separated and reach nothing at all when they are written any other way.
     */
    #[Test]
    public function aggregateStaticTemplateDeliversTheComponentTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_bite_jobs/Configuration/TypoScript/Full');

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            '<div id="constant">EXT:academic_bite_jobs/Resources/Private/Templates/BiteJobs/</div>',
            $body,
            'The aggregate static template did not deliver "constants.typoscript" of the component.',
        );
        $this->assertStringContainsString(
            '<div id="setup">EXT:academic_bite_jobs/Resources/Private/Templates/</div>',
            $body,
            'The aggregate static template did not deliver "setup.typoscript" of the component.',
        );
    }

    /**
     * The hide half, asserted on its own. Without it the re-enable assertions of the
     * other delivery tests cannot fail: they check that the content element is absent
     * from `removeItems`, and an empty list satisfies that just as well as a correct
     * one.
     */
    #[Test]
    public function theContentElementIsHiddenWithoutASiteSet(): void
    {
        $this->setUpSite();

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);
        $removeItems = GeneralUtility::trimExplode(
            ',',
            (string)($pageTsConfig['TCEFORM.']['tt_content.']['CType.']['removeItems'] ?? ''),
            true,
        );

        $this->assertContains(
            'academicbitejobs_list',
            $removeItems,
            'The content element is selectable although no set and no page TSconfig enable it.',
        );
    }
}
