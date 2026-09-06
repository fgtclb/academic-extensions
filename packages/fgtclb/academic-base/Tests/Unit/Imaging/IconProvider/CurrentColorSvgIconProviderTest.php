<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Imaging\IconProvider;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The two `source` guards are the provider's own and run on both cores. Everything
 * after them is the inline pipeline, and that pipeline differs per core: on TYPO3 v13 it
 * is a file read, an `enshrined/svg-sanitize` pass and a re-serialisation on a bare
 * instance - exactly the shape `IconFactory` creates there with `new` - so those cases are
 * unit tests on v13. On TYPO3 v14 the parent reads the file through
 * `SystemResourceFactory` and sanitises it through `SvgDocumentFactory`, all container
 * services with constructor dependencies of their own, so the same cases run as functional
 * tests on both cores in
 * `Tests/Functional/Imaging/IconProvider/CurrentColorSvgIconProviderTest.php`.
 *
 * The three files under `Fixtures/Icons/` are byte for byte the ones in the functional
 * fixture extension `test_current_color_icons`, kept twice so each suite stays
 * self-contained - an edit to one is an edit to both.
 */
final class CurrentColorSvgIconProviderTest extends UnitTestCase
{
    #[Test]
    public function defaultMarkupRequiresASource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1788480163);
        $this->expectExceptionMessage('[some-icon]');

        (new CurrentColorSvgIconProvider())->prepareIconMarkup((new Icon())->setIdentifier('some-icon'), []);
    }

    #[Test]
    public function defaultMarkupRejectsAnEmptySource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1788480163);

        (new CurrentColorSvgIconProvider())->prepareIconMarkup((new Icon())->setIdentifier('some-icon'), ['source' => '']);
    }

    #[Test]
    #[Group('not-core-14')]
    public function bothMarkupsInlineTheFile(): void
    {
        $icon = $this->prepareIcon(__DIR__ . '/Fixtures/Icons/arrow.svg');

        $markup = $icon->getMarkup();
        $this->assertStringStartsWith('<svg', $markup);
        $this->assertStringContainsString('viewBox="0 0 16 16"', $markup);
        $this->assertStringContainsString('fill="currentColor"', $markup);
        $this->assertStringNotContainsString('<img', $markup);
        $this->assertSame($markup, $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    /**
     * The sanitiser removes every node that is neither an element nor text, so a comment
     * goes with them. That is the same outcome TYPO3 v14 has always had; a licence
     * attribution stays in the source file for whoever reads the repository and has to be
     * given elsewhere in the delivered output.
     */
    #[Test]
    #[Group('not-core-14')]
    public function licenceCommentIsDroppedBySanitizer(): void
    {
        $icon = $this->prepareIcon(__DIR__ . '/Fixtures/Icons/arrow.svg');

        $markup = $icon->getMarkup();
        $this->assertStringNotContainsString('<!--', $markup);
        $this->assertStringContainsString('<path fill="currentColor"', $markup);
    }

    /**
     * A `<script>` element is what TYPO3 v13's own `getInlineSvg()` removes, and it is the
     * only thing it removes: an event handler attribute and a `javascript:` href reach the
     * markup untouched there. Rendering the file as an `<img>` neutralised that, inlining
     * it does not, so this provider runs the same sanitiser pass TYPO3 v14 runs. Each of
     * the three has to be gone.
     */
    #[Test]
    #[Group('not-core-14')]
    public function activeContentIsStripped(): void
    {
        $icon = $this->prepareIcon(__DIR__ . '/Fixtures/Icons/scripted.svg');

        $markup = $icon->getMarkup();
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
     * `<svg>` root - well-formed XML under an `.svg` name, a `<symbol>` fragment exported on
     * its own. Neither `SvgSanitizer` nor the parent catches it, so without the provider's
     * own guard the exception leaves the icon rendering and a record list or a page tree
     * answers 500 instead of showing an icon less. Every other unusable source degrades to
     * the empty markup here, and so does this one.
     */
    #[Test]
    #[Group('not-core-14')]
    public function fileWithoutAnSvgRootRendersEmptyMarkup(): void
    {
        $icon = $this->prepareIcon(__DIR__ . '/Fixtures/Icons/wrong-root.svg');

        $this->assertSame('', $icon->getMarkup());
        $this->assertSame('', $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    #[Test]
    #[Group('not-core-14')]
    public function missingFileRendersEmptyMarkup(): void
    {
        $icon = $this->prepareIcon(__DIR__ . '/Fixtures/Icons/missing.svg');

        $this->assertSame('', $icon->getMarkup());
        $this->assertSame('', $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    private function prepareIcon(string $source): Icon
    {
        $icon = (new Icon())->setIdentifier('test-icon');
        (new CurrentColorSvgIconProvider())->prepareIconMarkup($icon, ['source' => $source]);
        return $icon;
    }
}
