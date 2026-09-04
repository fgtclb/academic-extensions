<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging\IconProvider;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Group;
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
     * The two cores differ here, and both outcomes are pinned. TYPO3 v13 re-serialises
     * the file with `simplexml` and keeps the comment. TYPO3 v14 sanitises it through
     * `enshrined/svg-sanitize`, whose `Sanitizer::cleanUnsafeNodes()` removes every node
     * that is neither an element nor text - a comment goes with them, so a licence
     * attribution inside the file does not reach the markup there.
     */
    #[Test]
    #[Group('not-core-14')]
    public function licenceCommentSurvivesInliningOnCore13(): void
    {
        $markup = $this->getIcon('test-current-color-arrow')->getMarkup();

        $this->assertStringStartsWith('<svg', $markup);
        $this->assertStringContainsString('<!-- Test Icons v1.0 - https://example.com/icons - License: CC BY 4.0 -->', $markup);
    }

    #[Test]
    #[Group('not-core-13')]
    public function licenceCommentIsDroppedBySanitizerOnCore14(): void
    {
        $markup = $this->getIcon('test-current-color-arrow')->getMarkup();

        $this->assertStringStartsWith('<svg', $markup);
        $this->assertStringNotContainsString('<!--', $markup);
        $this->assertStringContainsString('<path fill="currentColor"', $markup);
    }

    #[Test]
    public function scriptElementIsStripped(): void
    {
        $markup = $this->getIcon('test-current-color-scripted')->getMarkup();

        $this->assertStringNotContainsString('<script', $markup);
        $this->assertStringNotContainsString('alert(', $markup);
        $this->assertStringContainsString('<path fill="currentColor"', $markup);
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
