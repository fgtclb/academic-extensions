<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\DataProcessing;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Lists every icon the academic extensions register, for the icon overview
 * content element of the development seed.
 *
 * The list is read from the icon registry on every render and never written
 * down, so an icon registered, renamed or dropped by an extension is on the
 * page, renamed or gone without anybody touching this package. What counts as
 * "ours" is decided by the identifier alone:
 *
 * - `tx-academic*`: the scheme of `Configuration/Icons.php`,
 *   `tx-<extension key without underscores>-<group>-<name>`, grouped by its
 *   second and third segment;
 * - `category_types.<group>.<type>`: what EXT:category_types registers for the
 *   `CategoryTypes.yaml` of every extension, grouped by `<group>`.
 *
 * The result is `[{title: string, groups: array<string, list<string>>}]`,
 * sections ordered by title, the groups of a section in the order of the
 * identifier scheme, identifiers alphabetically.
 */
#[AutoconfigureTag('data.processor', ['identifier' => 'academics-dev-site-icon-overview'])]
final readonly class IconOverviewProcessor implements DataProcessorInterface
{
    private const SCHEME_PREFIX = 'tx-academic';

    private const CATEGORY_TYPE_PREFIX = 'category_types.';

    /**
     * The groups of the identifier scheme, in the order the page shows them. A
     * group outside the scheme is shown after them rather than dropped: it is a
     * registration to look at, not one to hide.
     */
    private const GROUP_ORDER = ['action', 'state', 'info', 'record', 'plugin', 'doktype'];

    public function __construct(
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
        $sections = [];
        foreach ($this->iconRegistry->getAllRegisteredIconIdentifiers() as $identifier) {
            $identifier = (string)$identifier;
            $position = $this->positionOf($identifier);
            if ($position !== null) {
                $sections[$position[0]][$position[1]][] = $identifier;
            }
        }
        ksort($sections);

        $result = [];
        foreach ($sections as $title => $groups) {
            uksort($groups, fn(string $a, string $b): int => [$this->rankOf($a), $a] <=> [$this->rankOf($b), $b]);
            $result[] = [
                'title' => (string)$title,
                'groups' => array_map(static function (array $identifiers): array {
                    sort($identifiers);
                    return $identifiers;
                }, $groups),
            ];
        }

        $processedData[(string)$cObj->stdWrapValue('as', $processorConfiguration, 'iconSections')] = $result;

        return $processedData;
    }

    /**
     * @return array{0: string, 1: string}|null The section and the group of an
     *         identifier of ours, `null` for any other.
     */
    private function positionOf(string $identifier): ?array
    {
        if (str_starts_with($identifier, self::CATEGORY_TYPE_PREFIX)) {
            $parts = explode('.', $identifier, 3);
            return ['category_types', $parts[1] ?? ''];
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
