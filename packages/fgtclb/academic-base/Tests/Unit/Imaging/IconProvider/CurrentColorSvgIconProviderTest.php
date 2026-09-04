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
 * after them is the parent's inline pipeline, and that pipeline differs per core: on
 * TYPO3 v13 it is a file read plus a `<script>` strip on a bare instance - exactly the
 * shape `IconFactory` creates there with `new` - so those cases are unit tests on v13. On
 * TYPO3 v14 the parent reads the file through `SystemResourceFactory` and sanitises it
 * through `SvgDocumentFactory`, all container services with constructor dependencies
 * of their own, so the same cases run as functional tests on both cores in
 * `Tests/Functional/Imaging/IconProvider/CurrentColorSvgIconProviderTest.php`.
 *
 * The two files under `Fixtures/Icons/` are byte for byte the ones in the functional
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

    #[Test]
    #[Group('not-core-14')]
    public function licenceCommentSurvivesInlining(): void
    {
        $icon = $this->prepareIcon(__DIR__ . '/Fixtures/Icons/arrow.svg');

        $this->assertStringContainsString('<!-- Test Icons v1.0 - https://example.com/icons - License: CC BY 4.0 -->', $icon->getMarkup());
    }

    #[Test]
    #[Group('not-core-14')]
    public function scriptElementIsStripped(): void
    {
        $icon = $this->prepareIcon(__DIR__ . '/Fixtures/Icons/scripted.svg');

        $markup = $icon->getMarkup();
        $this->assertStringNotContainsString('<script', $markup);
        $this->assertStringNotContainsString('alert(', $markup);
        $this->assertStringContainsString('<path fill="currentColor"', $markup);
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
