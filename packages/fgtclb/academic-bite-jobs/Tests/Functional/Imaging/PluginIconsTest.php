<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Imaging;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The content element icon is drawn in `currentColor` and registered with the colour
 * scheme aware provider, so the page module and the new content element wizard show it in
 * the text colour of either backend colour scheme instead of as an `<img>` in the fixed
 * colours of its file.
 *
 * The page module takes it from TCA `typeicon_classes`, which the `icon` of the select item
 * writes; the wizard takes it from page TSconfig, which
 * `Tests/Functional/TsConfig/NewContentElementWizardRegistrationTest` asserts against the
 * same identifier.
 */
final class PluginIconsTest extends AbstractAcademicBiteJobsTestCase
{
    use ColourSchemeAwareIconsTrait;

    private const PLUGIN_ICON = 'tx-academicbitejobs-plugin-bite-jobs';

    #[Test]
    public function contentElementIsShownWithThePluginIcon(): void
    {
        $this->assertSame(
            self::PLUGIN_ICON,
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['academicbitejobs_list'] ?? null,
        );
    }

    #[Test]
    public function pluginIconIsRegisteredWithTheColourSchemeAwareProvider(): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider(self::PLUGIN_ICON);
    }

    #[Test]
    public function pluginIconIsInlinedInBothMarkups(): void
    {
        $this->assertIconIsInlinedInBothMarkups(self::PLUGIN_ICON);
    }

    #[Test]
    public function pluginIconMarkupFollowsTheTextColour(): void
    {
        $this->assertIconMarkupFollowsTheTextColour(self::PLUGIN_ICON);
    }

    #[Test]
    public function renderedPluginIconCarriesItsIdentifier(): void
    {
        $this->assertRenderedIconCarriesItsIdentifier(self::PLUGIN_ICON);
    }

    #[Test]
    public function pluginIconIsInTheHouseFormat(): void
    {
        $this->assertIconIsInTheHouseFormat(self::PLUGIN_ICON);
    }

    /**
     * The identifier above is spelled out. This walks the TCA instead: the content element
     * type named here has to name a registered, colour scheme aware identifier of this
     * extension - not a core or a foreign one, and not none. A content element added later
     * has to be added to the list.
     */
    #[Test]
    public function everyRecordTypeIconOfThisExtensionIsColourSchemeAware(): void
    {
        $this->assertEveryRecordTypeIconIsColourSchemeAware(
            'academic_bite_jobs',
            contentTypes: ['academicbitejobs_list'],
        );
    }

    #[Test]
    public function identifiersFollowTheNamingScheme(): void
    {
        $this->assertIconIdentifiersFollowTheNamingScheme('academic_bite_jobs', ['plugin']);
    }

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_bite_jobs');
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('academic_bite_jobs');
    }
}
