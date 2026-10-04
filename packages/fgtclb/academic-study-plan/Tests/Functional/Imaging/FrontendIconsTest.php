<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * The three controls of the study plan are frontend icons: registered in
 * `Configuration/FrontendIcons.php` and rendered by `ab:icon` of academic_base. The
 * backend never shows them, so the icon registry of the backend must not know them, or a
 * site that replaces one in `Configuration/Icons.php` sees no effect and no error. The
 * content element icon and the record icons are the opposite case, backend icons only.
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
            'academic-study-plan-plus' => 'plus.svg',
            'academic-study-plan-minus' => 'minus.svg',
            'academic-study-plan-close' => 'close.svg',
        ];
        foreach ($files as $identifier => $file) {
            yield $identifier => [$identifier, $file];
        }
    }

    #[Test]
    #[DataProvider('controlIcons')]
    public function controlIconIsAFrontendIconWithTheShippedFile(string $identifier, string $file): void
    {
        $this->assertFrontendIconIsRegisteredWithProvider($identifier, SvgIconProvider::class);
        $this->assertSame(
            'EXT:academic_study_plan/Resources/Public/Icons/' . $file,
            $this->get(FrontendIconRegistry::class)->getIconConfiguration($identifier)['options']['source'] ?? null,
        );
    }

    /**
     * The partials ask for the `inline` markup, which is the file drawn in `currentColor`.
     */
    #[Test]
    #[DataProvider('controlIcons')]
    public function controlIconIsInlinedOnRequest(string $identifier, string $file): void
    {
        $markup = $this->getFrontendIcon($identifier)->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE);

        $this->assertStringStartsWith('<svg', $markup);
        $this->assertStringContainsString('stroke="currentColor"', $markup);
    }

    #[Test]
    #[DataProvider('controlIcons')]
    public function renderedControlIconCarriesItsIdentifier(string $identifier, string $file): void
    {
        $this->assertRenderedFrontendIconCarriesItsIdentifier($identifier);
    }

    /**
     * Asked through the icon API of the backend, the way `core:icon` asks, the identifier
     * is unknown and the answer is TYPO3's placeholder.
     */
    #[Test]
    #[DataProvider('controlIcons')]
    public function controlIconIsNoBackendIcon(string $identifier, string $file): void
    {
        $this->assertFrontendIconIsNotABackendIcon($identifier);
        $this->assertSame(
            'default-not-found',
            $this->get(IconFactory::class)->getIcon($identifier, IconSize::SMALL)->getIdentifier(),
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function backendIconIdentifiers(): \Generator
    {
        yield from RecordIconsTest::recordIconIdentifiers();
        yield 'academic-study-plan' => ['academic-study-plan'];
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
}
