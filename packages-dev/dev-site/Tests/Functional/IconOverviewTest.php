<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * The icon overview content element of the seed lists what the two icon registries
 * hold, each in its own section, and renders every entry as inline SVG.
 *
 * The element exists to look at the icons of the academic extensions in a real
 * frontend, and it is only worth that while its lists are the registries' lists and
 * every entry renders the markup the extensions get: a frontend template through
 * `ab:icon`, the backend through `core:icon`.
 */
final class IconOverviewTest extends AbstractIconOverviewTestCase
{
    /**
     * Written down rather than read back, so an empty or truncated list cannot pass
     * by agreeing with itself, and so a section that lists the other registry
     * cannot either: one icon of each frontend group of the shared set, a record, a
     * plugin and two page type icons, and a category type icon, which
     * EXT:category_types puts into both registries.
     */
    #[Test]
    public function listsARepresentativeSetOfIdentifiersInTheirRegistry(): void
    {
        $rendered = $this->renderIconOverview()['icons'];

        foreach ([
            'tx-academicbase-action-add' => ['frontend'],
            'tx-academicbase-state-hidden' => ['frontend'],
            'tx-academicbase-info-phone' => ['frontend'],
            'tx-academicpersons-record-profile' => ['backend'],
            'tx-academicpersons-plugin-persons' => ['backend'],
            'tx-academicpartners-doktype-partner' => ['backend'],
            'tx-academicprograms-doktype-program' => ['backend'],
            'category_types.programs.admission_restriction' => ['frontend', 'backend'],
            'category_types_group.programs' => ['frontend', 'backend'],
        ] as $identifier => $registries) {
            foreach (['frontend', 'backend'] as $registry) {
                $this->assertSame(
                    in_array($registry, $registries, true),
                    array_key_exists($identifier, $rendered[$registry] ?? []),
                    sprintf('"%s" is %s the %s section.', $identifier, in_array($registry, $registries, true) ? 'not in' : 'in', $registry),
                );
            }
        }
    }

    /**
     * Exactly the registered identifiers of the academic extensions and of the
     * category types per registry - none missing, nothing of core, no placeholder -
     * each rendered as often as the element renders an icon, and each time as an
     * inline `<svg>`. A placeholder or an `<img>` fails the second half.
     */
    #[Test]
    public function listsEveryRegisteredIdentifierOfOursAsInlineSvg(): void
    {
        $rendered = $this->renderIconOverview()['icons'];

        foreach ($this->expectedIdentifiers() as $registry => [$expected, $floor]) {
            $actual = array_keys($rendered[$registry] ?? []);
            sort($actual);

            $this->assertGreaterThan($floor, count($expected), sprintf('The %s registry holds too few icons of ours to be the whole set.', $registry));
            $this->assertSame($expected, $actual, sprintf('The %s section does not list the %s registry.', $registry, $registry));
            foreach ($rendered[$registry] as $identifier => $renderings) {
                $this->assertSame(
                    array_fill(0, self::RENDERINGS_PER_IDENTIFIER, true),
                    $renderings,
                    sprintf('"%s" is not rendered as inline SVG every time in the %s section.', $identifier, $registry),
                );
            }
        }
    }

    /**
     * A tile says when the other registry holds its identifier as well, which after
     * the group rule is true for the category type and group icons only.
     */
    #[Test]
    public function marksTheIdentifiersBothRegistriesHold(): void
    {
        $inBoth = $this->renderIconOverview()['inBoth'];

        $expected = $this->expectedIdentifiers();
        $shared = array_values(array_intersect($expected['frontend'][0], $expected['backend'][0]));

        $this->assertContains('category_types.programs.admission_restriction', $shared);
        foreach (['frontend', 'backend'] as $registry) {
            $marked = $inBoth[$registry] ?? [];
            sort($marked);
            $this->assertSame($shared, $marked, sprintf('The %s section marks other tiles than the shared identifiers.', $registry));
        }
    }

    /**
     * The identifiers of ours per registry, sorted, with the floor the count has to
     * exceed. Measured when the page was added: 79 in the frontend registry (56
     * `tx-academic*` icons of academic_base, academic_jobs and academic_programs,
     * and 23 category type and group icons), 55 in the backend registry (32 record,
     * plugin and page type icons, and the same 23).
     *
     * @return array{frontend: array{0: list<string>, 1: int}, backend: array{0: list<string>, 1: int}}
     */
    private function expectedIdentifiers(): array
    {
        $ours = static function (array $identifiers): array {
            $identifiers = array_values(array_filter(
                array_map('strval', $identifiers),
                static fn(string $identifier): bool => str_starts_with($identifier, 'tx-academic')
                    || str_starts_with($identifier, 'category_types.')
                    || str_starts_with($identifier, 'category_types_group.'),
            ));
            sort($identifiers);
            return $identifiers;
        };

        return [
            'frontend' => [$ours($this->get(FrontendIconRegistry::class)->getAllRegisteredIconIdentifiers()), 70],
            'backend' => [$ours($this->get(IconRegistry::class)->getAllRegisteredIconIdentifiers()), 50],
        ];
    }
}
