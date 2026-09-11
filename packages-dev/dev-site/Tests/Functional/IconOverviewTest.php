<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * The icon overview content element of the seed lists what the icon registry
 * holds, and renders every entry as inline SVG.
 *
 * The element exists to look at the icons of the academic extensions in a real
 * frontend, and it is only worth that while its list is the registry's list and
 * every entry renders the markup a frontend template of the extensions gets.
 */
final class IconOverviewTest extends AbstractIconOverviewTestCase
{
    /**
     * Written down rather than read back, so an empty or truncated list cannot
     * pass by agreeing with itself: one icon of each group of the shared set,
     * a record, a plugin and a page type icon of other extensions, and a
     * category type icon, which EXT:category_types registers at boot rather
     * than through a `Configuration/Icons.php`.
     */
    #[Test]
    public function listsARepresentativeSetOfIdentifiers(): void
    {
        $rendered = $this->renderIconOverview();

        foreach ([
            'tx-academicbase-action-add',
            'tx-academicbase-state-hidden',
            'tx-academicbase-info-phone',
            'tx-academicpersons-record-profile',
            'tx-academicpersons-plugin-persons',
            'tx-academicpartners-doktype-partner',
            'tx-academicprograms-doktype-program',
            'category_types.programs.admission_restriction',
        ] as $identifier) {
            $this->assertArrayHasKey($identifier, $rendered, sprintf('"%s" is not on the page.', $identifier));
        }
    }

    /**
     * Exactly the registered identifiers of the academic extensions and of the
     * category types - none missing, nothing of core - each rendered as often as
     * the element renders an icon, and each time as an inline `<svg>`. A
     * placeholder or an `<img>` fails the second half.
     */
    #[Test]
    public function listsEveryRegisteredIdentifierOfOursAsInlineSvg(): void
    {
        $rendered = $this->renderIconOverview();

        $expected = array_values(array_filter(
            array_map('strval', $this->get(IconRegistry::class)->getAllRegisteredIconIdentifiers()),
            static fn(string $identifier): bool => str_starts_with($identifier, 'tx-academic')
                || str_starts_with($identifier, 'category_types.'),
        ));
        sort($expected);
        $actual = array_keys($rendered);
        sort($actual);

        $this->assertGreaterThan(80, count($expected), 'The registry holds too few icons of ours to be the whole set.');
        $this->assertSame($expected, $actual);
        foreach ($rendered as $identifier => $renderings) {
            $this->assertSame(
                array_fill(0, self::RENDERINGS_PER_IDENTIFIER, true),
                $renderings,
                sprintf('"%s" is not rendered as inline SVG every time.', $identifier),
            );
        }
    }
}
