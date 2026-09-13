<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;

/**
 * An icon an extension registers is on the overview without anybody touching
 * this package, and an icon outside the prefixes of the academic extensions is
 * not.
 *
 * That is the difference between a list read from the registry when the
 * element renders and a list somebody keeps. Two fixture extensions of other
 * packages stand in for "an extension": the category types of
 * `tests/category-types-icons` are `category_types.testicons.*` and belong on
 * the page, the icons of `tests/current-color-icons` are `test-current-color-*`
 * and do not.
 */
final class IconOverviewFixtureIconsTest extends AbstractIconOverviewTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad = [
            ...$this->testExtensionsToLoad,
            'tests/category-types-icons',
            'tests/current-color-icons',
        ];
        parent::setUp();
    }

    #[Test]
    public function listsTheIconsAnExtensionAddsWithinOurPrefixesOnly(): void
    {
        $rendered = $this->renderIconOverview();

        $this->assertSame(
            array_fill(0, self::RENDERINGS_PER_IDENTIFIER, true),
            $rendered['category_types.testicons.vector'] ?? null,
            'The category type icon a fixture extension registers is not on the page as inline SVG.',
        );
        $this->assertSame(
            [],
            array_values(array_filter(
                array_keys($rendered),
                static fn(string $identifier): bool => str_starts_with($identifier, 'test-current-color-'),
            )),
            'Icons outside the prefixes of the academic extensions are on the page.',
        );
    }
}
