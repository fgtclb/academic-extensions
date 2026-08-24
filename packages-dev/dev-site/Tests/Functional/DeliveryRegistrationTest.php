<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Every path the legacy tree is delivered through still exists and is still
 * offered.
 *
 * The `/legacy/` tree is configured the way an installation that predates site
 * sets is configured: a root `sys_template` record whose `include_static_file`
 * names nine static TypoScript folders, and a root page whose
 * `tsconfig_includes` names eleven page TSconfig files. Both columns are comma
 * separated lists of `EXT:` paths, and both are read with `trimExplode` and a
 * silent skip - a path that no longer resolves contributes nothing, reports
 * nothing, and the page renders with a piece of its configuration missing.
 *
 * That is the failure this test exists for, and it is checked twice:
 *
 * - **the path resolves**, which catches a renamed or moved folder;
 * - **the path is a registered item**, which catches the case the first check
 *   cannot see: a folder that is still on disk and is no longer offered by
 *   `addStaticFile()` or `registerPageTSConfigFile()`. An integrator cannot
 *   select it any more, so the seed is describing an installation nobody could
 *   build - and the site sets of the `/` tree have taken the delivery over
 *   without anybody saying so out loud.
 *
 * The values are read from the imported records rather than from the YAML, so
 * what is checked is what the seed writes and not what it appears to write.
 */
final class DeliveryRegistrationTest extends AbstractSeedTestCase
{
    #[Test]
    public function everyStaticTemplateOfTheLegacyRootResolvesAndIsRegistered(): void
    {
        $this->importSeed();

        $this->assertPathsResolveAndAreRegistered(
            $this->columnValue('sys_template', $this->deliveryTemplateUid(), 'include_static_file'),
            $this->registeredItems('sys_template', 'include_static_file'),
            'include_static_file of the "sys_template" record that delivers',
            $this->isStaticTemplateFolder(...),
        );
    }

    #[Test]
    public function everyPageTsConfigIncludeOfTheLegacyRootResolvesAndIsRegistered(): void
    {
        $this->importSeed();

        $this->assertPathsResolveAndAreRegistered(
            $this->columnValue('pages', $this->deliveryRootPageUid(), 'tsconfig_includes'),
            $this->registeredItems('pages', 'tsconfig_includes'),
            'tsconfig_includes of the page that delivers',
            static function (string $path): bool {
                $file = GeneralUtility::getFileAbsFileName($path);

                return $file !== '' && is_file($file);
            },
        );
    }

    /**
     * The extension key of an `EXT:` path, or null when the entry is not one.
     */
    private static function extensionKeyOf(string $entry): ?string
    {
        if (!str_starts_with($entry, 'EXT:')) {
            return null;
        }

        $rest = substr($entry, 4);
        $slash = strpos($rest, '/');

        return $slash === false ? $rest : substr($rest, 0, $slash);
    }

    /**
     * The record that carries the static template, and the page that carries the
     * page TSconfig list - which is not the same page on both core versions.
     *
     * From TYPO3 v13 the instance imports the `-sets` variant of the seed: its
     * `/` tree is delivered through site sets and the `sys_template` record sits
     * on the root of the `/legacy/` mirror, page 1001, with uid 1. On v12 there
     * are no site sets and no mirror: one tree, delivered by a `sys_template`
     * with uid 900 on page 1. The uid is 900 rather than 1 because an id is
     * unique among the siblings of one parent and the first content element of
     * that page already declares 1.
     */
    private function deliveryTemplateUid(): int
    {
        return (new Typo3Version())->getMajorVersion() >= 13 ? 1 : 900;
    }

    private function deliveryRootPageUid(): int
    {
        return (new Typo3Version())->getMajorVersion() >= 13 ? 1001 : 1;
    }

    /**
     * @param list<string> $registered
     * @param callable(string): bool $resolves Whether the entry names something
     *        the reading code can use: a page TSconfig entry is a file, a static
     *        template entry is a folder with the right thing in it.
     */
    private function assertPathsResolveAndAreRegistered(
        string $value,
        array $registered,
        string $subject,
        callable $resolves,
    ): void {
        $entries = GeneralUtility::trimExplode(',', $value, true);
        $this->assertNotSame([], $entries, sprintf('The %s is empty.', $subject));

        // An entry of an extension this test instance does not load can be checked
        // by neither half of this test: the path cannot resolve, and the TCA that
        // would offer it is not there either. The `core-12` seed names the theme,
        // `EXT:bootstrap_package`, which the instances require and the repository
        // root does not - so those entries are counted and named rather than
        // silently dropped, and the defect this test exists for stays covered: a
        // path of *this* repository going stale after a restructure.
        $skipped = [];
        $entries = array_values(array_filter($entries, static function (string $entry) use (&$skipped): bool {
            $extensionKey = self::extensionKeyOf($entry);
            if ($extensionKey !== null && !ExtensionManagementUtility::isLoaded($extensionKey)) {
                $skipped[] = $entry;

                return false;
            }

            return true;
        }));
        $this->assertNotSame([], $entries, sprintf(
            "Every entry of the %s belongs to an extension this instance does not load:\n  %s",
            $subject,
            implode("\n  ", $skipped),
        ));

        $unresolved = [];
        $unregistered = [];
        foreach ($entries as $entry) {
            if (!$resolves($entry)) {
                $unresolved[] = $entry;
            }
            if (!in_array(rtrim($entry, '/'), $registered, true)) {
                $unregistered[] = $entry;
            }
        }

        $this->assertSame([], $unresolved, sprintf(
            "The %s names paths that do not resolve:\n  %s\n"
            . 'The column is read with trimExplode and a silent skip, so this does not raise anything at'
            . ' runtime - the page simply renders without that part of its configuration.',
            $subject,
            implode("\n  ", $unresolved),
        ));

        $this->assertSame([], $unregistered, sprintf(
            "The %s names paths that resolve and are no longer offered:\n  %s\n"
            . 'Nothing registers them through addStaticFile() or registerPageTSConfigFile() any more, so an'
            . ' integrator could not select them - the seed describes an installation that cannot be built'
            . ' by hand.',
            $subject,
            implode("\n  ", $unregistered),
        ));
    }

    /**
     * A static template folder, as the TypoScript include tree reads one.
     *
     * Any one of the three files is enough, and the third one is why this is not
     * a plain "setup.typoscript exists": the aggregate folders of the academic
     * extensions - `Configuration/TypoScript/Full/` - hold nothing but an
     * `include_static_file.txt` naming their component folders, and several of
     * those component folders do the same again.
     */
    private function isStaticTemplateFolder(string $path): bool
    {
        $directory = GeneralUtility::getFileAbsFileName(rtrim($path, '/'));
        if ($directory === '' || !is_dir($directory)) {
            return false;
        }

        foreach (['constants.typoscript', 'setup.typoscript', 'include_static_file.txt'] as $file) {
            if (is_file($directory . '/' . $file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The item values a column offers, normalised the way the seed writes them.
     *
     * The trailing slash is trimmed off both sides, because whether a
     * registration carries one is up to whoever registered it:
     * `addStaticFile()` passes the path through unchanged, so
     * EXT:fluid_styled_content offers `.../Configuration/TypoScript/` and the
     * academic extensions offer `.../Configuration/TypoScript/Full`. The
     * TypoScript include tree does not care, and neither should this.
     *
     * @return list<string>
     */
    private function registeredItems(string $table, string $column): array
    {
        $items = $GLOBALS['TCA'][$table]['columns'][$column]['config']['items'] ?? [];
        $this->assertNotSame([], $items, sprintf('"%s.%s" offers nothing at all.', $table, $column));

        $values = [];
        foreach ($items as $item) {
            $value = (string)($item['value'] ?? $item[1] ?? '');
            if ($value !== '') {
                $values[] = rtrim($value, '/');
            }
        }

        return $values;
    }

    private function columnValue(string $table, int $uid, string $column): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $value = $queryBuilder
            ->select($column)
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        $this->assertIsString($value, sprintf('"%s" uid %d has no "%s".', $table, $uid, $column));

        return $value;
    }
}
