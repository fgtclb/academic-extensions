<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * The three controls of the study plan are the shared action icons of academic_base:
 * registered in its `Configuration/FrontendIcons.php` and rendered by `ab:icon` from the
 * partials of this extension. The backend never shows them, so the icon registry of the
 * backend must not know them, or a site that replaces one in `Configuration/Icons.php`
 * sees no effect and no error. The content element icon and the record icons are the
 * opposite case, backend icons only.
 *
 * The identifiers and files are spelled out here rather than read back out of the
 * registration, so a rename has to be made twice instead of silently agreeing with itself.
 */
final class FrontendIconsTest extends AbstractAcademicStudyPlanTestCase
{
    use FrontendIconsAssertionTrait;

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function controlIcons(): \Generator
    {
        $files = [
            'tx-academicbase-action-expand' => 'action/expand.svg',
            'tx-academicbase-action-collapse' => 'action/collapse.svg',
            'tx-academicbase-action-close' => 'action/close.svg',
        ];
        foreach ($files as $identifier => $file) {
            yield $identifier => [$identifier, $file];
        }
    }

    #[Test]
    #[DataProvider('controlIcons')]
    public function controlIconIsASharedFrontendIcon(string $identifier, string $file): void
    {
        $this->assertFrontendIconIsRegisteredWithProvider($identifier, CurrentColorSvgIconProvider::class);
        $this->assertSame(
            'EXT:academic_base/Resources/Public/Icons/' . $file,
            $this->get(FrontendIconRegistry::class)->getIconConfiguration($identifier)['options']['source'] ?? null,
        );
    }

    /**
     * The partials ask for the `inline` markup: the file drawn in `currentColor`, with a
     * size of its own. An inline SVG with a `viewBox` and no size collapses to 0 px in the
     * flex header of a semester and in the close button of the dialog.
     */
    #[Test]
    #[DataProvider('controlIcons')]
    public function controlIconIsInlinedWithASizeOnRequest(string $identifier, string $file): void
    {
        $markup = $this->getFrontendIcon($identifier)->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE);

        $this->assertMatchesRegularExpression('#^<svg\b[^>]*\bwidth="1em"[^>]*>#', $markup);
        $this->assertMatchesRegularExpression('#^<svg\b[^>]*\bheight="1em"[^>]*>#', $markup);
        $this->assertMatchesRegularExpression('#^<svg\b[^>]*\bfill="currentColor"[^>]*>#', $markup);
    }

    #[Test]
    #[DataProvider('controlIcons')]
    public function renderedControlIconCarriesItsIdentifier(string $identifier, string $file): void
    {
        $this->assertRenderedFrontendIconCarriesItsIdentifier($identifier);
    }

    #[Test]
    #[DataProvider('controlIcons')]
    public function controlIconIsNoBackendIcon(string $identifier, string $file): void
    {
        $this->assertFrontendIconIsNotABackendIcon($identifier);
    }

    /**
     * Nothing is left for the extension to register in the frontend, so it ships no file
     * for it. One that came back would register identifiers no template renders.
     */
    #[Test]
    public function extensionRegistersNoFrontendIconOfItsOwn(): void
    {
        $this->assertFileDoesNotExist(ExtensionManagementUtility::extPath('academic_study_plan') . 'Configuration/FrontendIcons.php');
    }

    /**
     * The record icons and the content element icon are shown by the backend only, and
     * stay out of the frontend registry.
     */
    #[Test]
    #[DataProvider('backendIconIdentifiers')]
    public function backendIconIsNoFrontendIcon(string $identifier): void
    {
        $this->assertTrue($this->get(IconRegistry::class)->isRegistered($identifier));
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered($identifier));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function backendIconIdentifiers(): \Generator
    {
        yield from RecordIconsTest::recordIconIdentifiers();
    }
}
