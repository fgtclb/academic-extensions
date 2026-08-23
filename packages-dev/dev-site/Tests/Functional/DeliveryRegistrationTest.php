<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
            $this->columnValue('sys_template', 1, 'include_static_file'),
            $this->registeredItems('sys_template', 'include_static_file'),
            'include_static_file of the legacy root "sys_template" record',
            $this->isStaticTemplateFolder(...),
        );
    }

    #[Test]
    public function everyPageTsConfigIncludeOfTheLegacyRootResolvesAndIsRegistered(): void
    {
        $this->importSeed();

        $this->assertPathsResolveAndAreRegistered(
            $this->columnValue('pages', 1001, 'tsconfig_includes'),
            $this->registeredItems('pages', 'tsconfig_includes'),
            'tsconfig_includes of the legacy root page',
            static function (string $path): bool {
                $file = GeneralUtility::getFileAbsFileName($path);

                return $file !== '' && is_file($file);
            },
        );
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
