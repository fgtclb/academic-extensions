<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconFactory;
use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * The factory renders an icon of the frontend registry exactly as core's `IconFactory`
 * renders the same provider and options from the backend registry.
 *
 * The markup comparison is what proves the providers come from the container: TYPO3
 * v14 calls the `inject*()` setters of `AbstractSvgIconProvider` only for a provider
 * the container builds, and a provider created with `new` fails its first inline
 * render there. TYPO3 v13 renders either way.
 */
final class FrontendIconFactoryTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/frontend-icons',
    ];

    public static function fixtureIconsDataProvider(): \Generator
    {
        yield 'currentColor provider' => ['test-frontend-current-color'];
        yield 'core SVG provider' => ['test-frontend-svg'];
        yield 'bitmap provider' => ['test-frontend-bitmap'];
        yield 'detected provider' => ['test-frontend-detected'];
        yield 'scripted file' => ['test-frontend-scripted'];
        yield 'contributed by a listener' => ['test-frontend-contributed'];
        yield 'placeholder' => ['default-not-found'];
    }

    /**
     * The same configuration is registered in the backend registry under another
     * identifier and rendered by core, so the comparison covers the provider markup
     * and leaves out the wrapper, which carries the identifier.
     */
    #[DataProvider('fixtureIconsDataProvider')]
    #[Test]
    public function markupIsWhatCoreRendersForTheSameProviderAndOptions(string $identifier): void
    {
        $configuration = $this->get(FrontendIconRegistry::class)->getIconConfiguration($identifier);
        $this->assertNotNull($configuration);
        $this->get(IconRegistry::class)->registerIcon('compare-' . $identifier, $configuration['provider'], $configuration['options']);

        $frontendIcon = $this->get(FrontendIconFactory::class)->getIcon($identifier, IconSize::SMALL);
        $coreIcon = $this->get(IconFactory::class)->getIcon('compare-' . $identifier, IconSize::SMALL);

        $this->assertSame($identifier, $frontendIcon->getIdentifier());
        $this->assertNotSame('', $frontendIcon->getMarkup());
        $this->assertSame($coreIcon->getMarkup(), $frontendIcon->getMarkup());
        $this->assertSame(
            $coreIcon->getMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE),
            $frontendIcon->getMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE),
        );
    }

    /**
     * The scripted file is sanitised on the frontend path as on the backend path.
     */
    #[Test]
    public function activeContentIsStripped(): void
    {
        $markup = $this->get(FrontendIconFactory::class)->getIcon('test-frontend-scripted', IconSize::SMALL)->getMarkup();

        $this->assertStringContainsString('<path fill="currentColor"', $markup);
        $this->assertStringNotContainsString('<script', $markup);
        $this->assertStringNotContainsString('onload', $markup);
        $this->assertStringNotContainsString('javascript:', $markup);
    }

    /**
     * A registered icon whose file is missing is still that icon, with empty markup, as
     * the provider answers in the backend. It is not the placeholder.
     */
    #[Test]
    public function aMissingFileRendersTheIconWithEmptyMarkup(): void
    {
        $icon = $this->get(FrontendIconFactory::class)->getIcon('test-frontend-missing', IconSize::SMALL);

        $this->assertSame('test-frontend-missing', $icon->getIdentifier());
        $this->assertSame('', $icon->getMarkup());
        $this->assertStringContainsString('data-identifier="test-frontend-missing"', $icon->render());
    }

    #[Test]
    public function spinningAndBidiReachTheClasses(): void
    {
        $rendered = $this->get(FrontendIconFactory::class)->getIcon('test-frontend-spinning', IconSize::SMALL)->render();

        $this->assertStringContainsString(' icon-spin', $rendered);
        $this->assertStringContainsString(' icon-bidi', $rendered);
    }

    /**
     * Every call returns a copy of the prepared icon, so a title set on one rendering
     * cannot reach the next.
     */
    #[Test]
    public function everyCallReturnsAnIconOfItsOwn(): void
    {
        $factory = $this->get(FrontendIconFactory::class);

        $first = $factory->getIcon('test-frontend-current-color', IconSize::SMALL);
        $first->setTitle('Call');
        $second = $factory->getIcon('test-frontend-current-color', IconSize::SMALL);

        $this->assertNotSame($first, $second);
        $this->assertNull($second->getTitle());
        $this->assertSame($first->getMarkup(), $second->getMarkup());
    }

    #[Test]
    public function anUnknownIdentifierAndAnUnknownOverlayRenderThePlaceholder(): void
    {
        $icon = $this->get(FrontendIconFactory::class)->getIcon('test-frontend-fone', IconSize::SMALL, 'test-frontend-unknown-overlay');

        $this->assertSame('default-not-found', $icon->getIdentifier());
        $this->assertStringStartsWith('<img', $icon->getMarkup());
        $this->assertSame('default-not-found', $icon->getOverlayIcon()?->getIdentifier());
        $this->assertSame('overlay', $icon->getOverlayIcon()->getSize());
    }
}
