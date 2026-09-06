<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging\IconProvider;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * Renders icons registered with the provider the way production does: through the
 * `IconFactory` of the container, from a `Configuration/Icons.php` with `EXT:` sources.
 * That is what proves the provider is wired on each core - TYPO3 v14 publishes it as an
 * `icon.provider` service and calls the parent's `inject*()` setters, TYPO3 v13 creates
 * it with `new` - and it is the only place the v14 inline pipeline (`SystemResourceFactory`
 * plus `SvgDocumentFactory`) can be measured rather than assumed.
 */
final class CurrentColorSvgIconProviderTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
        'tests/current-color-icons',
    ];

    #[Test]
    public function defaultMarkupInlinesTheFile(): void
    {
        $markup = $this->getIcon('test-current-color-arrow')->getMarkup();

        $this->assertStringStartsWith('<svg', $markup);
        $this->assertStringContainsString('viewBox="0 0 16 16"', $markup);
        $this->assertStringContainsString('fill="currentColor"', $markup);
        $this->assertStringNotContainsString('<img', $markup);
    }

    #[Test]
    public function inlineMarkupEqualsDefaultMarkup(): void
    {
        $icon = $this->getIcon('test-current-color-arrow');

        $this->assertSame($icon->getMarkup(), $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    #[Test]
    public function renderedIconCarriesTheIdentifierAroundTheInlinedFile(): void
    {
        $rendered = $this->getIcon('test-current-color-arrow')->render();

        $this->assertStringContainsString('data-identifier="test-current-color-arrow"', $rendered);
        $this->assertStringContainsString('<span class="icon-markup">', $rendered);
        $this->assertStringContainsString('<svg', $rendered);
        $this->assertStringNotContainsString('<img', $rendered);
    }

    /**
     * Both cores sanitise now, and `Sanitizer::cleanUnsafeNodes()` removes every node that
     * is neither an element nor text - a comment goes with them. A licence attribution
     * inside the file therefore does not reach the rendered page on either core; it stays
     * in the source for whoever reads the repository and has to be given elsewhere where
     * the licence wants it in the output.
     */
    #[Test]
    public function licenceCommentIsDroppedBySanitizer(): void
    {
        $markup = $this->getIcon('test-current-color-arrow')->getMarkup();

        $this->assertStringStartsWith('<svg', $markup);
        $this->assertStringNotContainsString('<!--', $markup);
        $this->assertStringContainsString('<path fill="currentColor"', $markup);
    }

    /**
     * TYPO3 v13's `getInlineSvg()` removes `<script>` elements and nothing else - an
     * `onload`, an `onclick` and a `javascript:` href all reach the markup there. That was
     * harmless while the default markup was an `<img>`; this provider inlines the file into
     * the document, so the provider runs the sanitiser itself on v13. All three have to be
     * gone on both cores, and the drawing has to survive.
     */
    #[Test]
    public function activeContentIsStripped(): void
    {
        $markup = $this->getIcon('test-current-color-scripted')->getMarkup();

        $this->assertStringNotContainsString('<script', $markup);
        $this->assertStringNotContainsString('alert(', $markup);
        $this->assertStringNotContainsString('onload', $markup);
        $this->assertStringNotContainsString('onclick', $markup);
        $this->assertStringNotContainsString('javascript:', $markup);
        $this->assertStringContainsString('<path fill="currentColor"', $markup);
    }

    /**
     * `enshrined/svg-sanitize` throws `\LogicException` 1570870568 out of
     * `XPath::handleDefaultNamespace()` for a document that does not carry exactly one
     * `<svg>` root. Nothing below the provider catches it on either core: TYPO3 v13 runs the
     * sanitiser through `SvgSanitizer` here, and on TYPO3 v14
     * `AbstractSvgIconProvider::getInlineSvg()` catches `InvalidSvgException` only while
     * `SvgDocumentFactory::fromStringAndSanitize()` runs the same sanitiser. Without the
     * provider's guard the exception leaves `IconFactory::getIcon()`, so a record list or a
     * page tree carrying such an icon answers 500 rather than rendering one icon less.
     */
    #[Test]
    public function fileWithoutAnSvgRootRendersEmptyMarkup(): void
    {
        $icon = $this->getIcon('test-current-color-wrong-root');

        $this->assertSame('', $icon->getMarkup());
        $this->assertSame('', $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    #[Test]
    public function missingFileRendersEmptyMarkup(): void
    {
        $icon = $this->getIcon('test-current-color-missing');

        $this->assertSame('', $icon->getMarkup());
        $this->assertSame('', $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    #[Test]
    public function coreProviderRendersTheSameFileAsImage(): void
    {
        $icon = $this->getIcon('test-current-color-arrow-image');

        $this->assertStringStartsWith('<img', $icon->getMarkup());
        $this->assertStringStartsWith('<svg', $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    private function getIcon(string $identifier): Icon
    {
        return $this->get(IconFactory::class)->getIcon($identifier, IconSize::SMALL);
    }
}
