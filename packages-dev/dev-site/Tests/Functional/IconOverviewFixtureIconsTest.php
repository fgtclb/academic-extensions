<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;

/**
 * An icon an extension registers is on the overview without anybody touching this
 * package, in the section of the registry it is registered in, and an icon outside
 * the prefixes of the academic extensions is not.
 *
 * That is the difference between lists read from the registries when the element
 * renders and a list somebody keeps. Fixture extensions of other packages stand in
 * for "an extension":
 *
 * - `tests/category-types-icons` declares category types and groups,
 *   `category_types.testicons.*` and `category_types_group.testicons*`, which
 *   EXT:category_types puts into both registries, and which belong on the page;
 * - `tests/current-color-icons` registers `test-current-color-*` in the icon
 *   registry of TYPO3 and `tests/frontend-icons` registers `test-frontend-*` in
 *   the frontend registry, and neither belongs on the page.
 */
final class IconOverviewFixtureIconsTest extends AbstractIconOverviewTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad = [
            ...$this->testExtensionsToLoad,
            'tests/category-types-icons',
            'tests/current-color-icons',
            'tests/frontend-icons',
        ];
        parent::setUp();
    }

    #[Test]
    public function listsTheIconsAnExtensionAddsWithinOurPrefixesOnly(): void
    {
        $rendered = $this->renderIconOverview()['icons'];

        foreach (['frontend', 'backend'] as $registry) {
            foreach ([
                'category_types.testicons.vector',
                'category_types.testicons.plain',
                'category_types_group.testicons',
                'category_types_group.testiconsinline',
            ] as $identifier) {
                // Counted, not checked for inline SVG: a fixture icon that does not
                // opt in to inlining is an <img> in the default markup, and that is
                // the provider's business, not the overview's.
                $this->assertCount(
                    self::RENDERINGS_PER_IDENTIFIER,
                    $rendered[$registry][$identifier] ?? [],
                    sprintf('"%s" of a fixture extension is not in the %s section.', $identifier, $registry),
                );
            }
            $this->assertSame(
                [],
                array_values(array_filter(
                    array_keys($rendered[$registry] ?? []),
                    static fn(string $identifier): bool => str_starts_with($identifier, 'test-'),
                )),
                sprintf('Icons outside the prefixes of the academic extensions are in the %s section.', $registry),
            );
        }
    }
}
