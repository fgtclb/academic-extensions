<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\DataProcessing;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Lists every icon the academic extensions register, in both icon registries, for
 * the icon overview content element of the development seed.
 *
 * The lists are read from the registries on every render and never written down,
 * so an icon registered, renamed or dropped by an extension is on the page, renamed
 * or gone without anybody touching this package. Two registries, because the group
 * of an identifier decides where it is registered: `action`, `state` and `info`
 * icons in the frontend registry of academic_base (`Configuration/FrontendIcons.php`,
 * rendered with `ab:icon`), `record`, `plugin` and `doktype` icons in the icon
 * registry of TYPO3 (`Configuration/Icons.php`, rendered with `core:icon`). The
 * category types put their icons into both.
 *
 * What counts as "ours" is decided by the identifier alone:
 *
 * - `tx-academic*`: `tx-<extension key without underscores>-<group>-<name>`,
 *   grouped by the extension prefix and the group;
 * - `category_types.<group>.<type>` and `category_types_group.<group>`: what
 *   EXT:category_types registers for the `CategoryTypes.yaml` of every extension,
 *   grouped by `<group>`, the group icon first.
 *
 * The result is one entry per registry, frontend first, `{name, otherName, count,
 * sections}`, a section being `{title, groups: list<{name, icons: list<{identifier,
 * inBoth}>}>}`. `inBoth` marks an identifier the other registry holds as well.
 */
#[AutoconfigureTag('data.processor', ['identifier' => 'academics-dev-site-icon-overview'])]
final readonly class IconOverviewProcessor implements DataProcessorInterface
{
    private const SCHEME_PREFIX = 'tx-academic';

    private const CATEGORY_TYPE_PREFIX = 'category_types.';

    private const CATEGORY_TYPE_GROUP_PREFIX = 'category_types_group.';

    private const CATEGORY_TYPES_SECTION = 'category_types';

    /**
     * The groups of the identifier scheme, in the order the page shows them. A
     * group outside the scheme is shown after them rather than dropped: it is a
     * registration to look at, not one to hide.
     */
    private const GROUP_ORDER = ['action', 'state', 'info', 'record', 'plugin', 'doktype'];

    public function __construct(
        private FrontendIconRegistry $frontendIconRegistry,
        private IconRegistry $iconRegistry,
    ) {}

    /**
     * @param array<string, mixed> $contentObjectConfiguration
     * @param array<string, mixed> $processorConfiguration
     * @param array<string, mixed> $processedData
     * @return array<string, mixed>
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        $frontend = $this->ours($this->frontendIconRegistry->getAllRegisteredIconIdentifiers());
        $backend = $this->ours(array_map('strval', $this->iconRegistry->getAllRegisteredIconIdentifiers()));

        $processedData[(string)$cObj->stdWrapValue('as', $processorConfiguration, 'iconRegistries')] = [
            $this->registry('frontend', $frontend, 'backend', $backend),
            $this->registry('backend', $backend, 'frontend', $frontend),
        ];

        return $processedData;
    }

    /**
     * @param list<string> $identifiers
     * @return list<string>
     */
    private function ours(array $identifiers): array
    {
        return array_values(array_filter(
            $identifiers,
            fn(string $identifier): bool => $this->positionOf($identifier) !== null,
        ));
    }

    /**
     * @param list<string> $identifiers
     * @param list<string> $otherIdentifiers
     * @return array{name: string, otherName: string, count: int, sections: list<array{title: string, groups: list<array{name: string, icons: list<array{identifier: string, inBoth: bool}>}>}>}
     */
    private function registry(string $name, array $identifiers, string $otherName, array $otherIdentifiers): array
    {
        $other = array_flip($otherIdentifiers);
        $sections = [];
        foreach ($identifiers as $identifier) {
            [$section, $group] = $this->positionOf($identifier) ?? ['', ''];
            $sections[$section][$group][] = $identifier;
        }
        ksort($sections);

        $result = [];
        foreach ($sections as $title => $groups) {
            // A numeric group name becomes an integer key, hence the casts.
            uksort($groups, fn(int|string $a, int|string $b): int => [$this->rankOf((string)$a), (string)$a] <=> [$this->rankOf((string)$b), (string)$b]);
            $groupList = [];
            foreach ($groups as $group => $groupIdentifiers) {
                // The group icon of a category type group ahead of its types.
                usort($groupIdentifiers, static fn(string $a, string $b): int => [
                    !str_starts_with($a, self::CATEGORY_TYPE_GROUP_PREFIX), $a,
                ] <=> [
                    !str_starts_with($b, self::CATEGORY_TYPE_GROUP_PREFIX), $b,
                ]);
                $groupList[] = [
                    'name' => (string)$group,
                    'icons' => array_map(static fn(string $identifier): array => [
                        'identifier' => $identifier,
                        'inBoth' => isset($other[$identifier]),
                    ], $groupIdentifiers),
                ];
            }
            $result[] = ['title' => (string)$title, 'groups' => $groupList];
        }

        return ['name' => $name, 'otherName' => $otherName, 'count' => count($identifiers), 'sections' => $result];
    }

    /**
     * @return array{0: string, 1: string}|null The section and the group of an
     *         identifier of ours, `null` for any other.
     */
    private function positionOf(string $identifier): ?array
    {
        if (str_starts_with($identifier, self::CATEGORY_TYPE_GROUP_PREFIX)) {
            return [self::CATEGORY_TYPES_SECTION, substr($identifier, strlen(self::CATEGORY_TYPE_GROUP_PREFIX))];
        }
        if (str_starts_with($identifier, self::CATEGORY_TYPE_PREFIX)) {
            $parts = explode('.', $identifier, 3);
            return [self::CATEGORY_TYPES_SECTION, $parts[1] ?? ''];
        }
        if (str_starts_with($identifier, self::SCHEME_PREFIX)) {
            $parts = explode('-', $identifier, 4);
            return [$parts[0] . '-' . $parts[1], $parts[2] ?? ''];
        }

        return null;
    }

    private function rankOf(string $group): int
    {
        $index = array_search($group, self::GROUP_ORDER, true);

        return $index === false ? count(self::GROUP_ORDER) : $index;
    }
}
