<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The link table at the top of every package `README.md` leads to the package's split
 * repository, to the manual of the branch the README lives on and to the TER page of the
 * extension.
 *
 * The README is the start page of the split repository on GitHub and of the package on
 * Packagist, and the TER shows it too. Its table was written by hand and copied from one
 * README to the next: until ACE-934 five of them linked a manual path that answered 404 and
 * four had no manual link at all. Nothing else reports that.
 *
 * - The table stands directly below the title, separated by one blank line, with an empty
 *   first header cell, `URL` as the second, and the rows "Repository", "Read online" and
 *   "TER" in that order. `lintMarkdown` checks its padding.
 * - "Repository" names the split repository, which is named after the package directory,
 *   not after the composer package: `fgtclb/typo3-category-types`.
 * - "Read online" names the manual docs.typo3.org renders for the branch the README lives
 *   on: `main` for the branch `main`, and the major and minor version of the package for a
 *   version branch, because docs.typo3.org publishes those manuals per release tag
 *   (`2.4` for `2.4.0`). The branch is the one {@see DocumentationGuidesTest::branch()}
 *   reads from the root, the version is the package's `VERSION`. A minor version bump on a
 *   version branch therefore fails here until the README follows.
 * - "TER" names the extension key.
 * - `-` stands for "Read online" only for a package in {@see self::MANUALS_NOT_RENDERED}.
 */
final class ReadmeLinkTableTest extends TestCase
{
    /**
     * The packages whose manual docs.typo3.org does not render yet, by package directory,
     * with the issue that makes it render. Their README says `-` for "Read online", and
     * only theirs. The issue removes its entry in the same change that links the manual,
     * which the test enforces: a listed package that links a manual fails.
     *
     * Empty since ACE-937 got the manual of `academic-base` rendered. A new package needs
     * an entry until docs.typo3.org has rendered its manual for the first time, which takes
     * the docs.typo3.org webhook in its split repository and an approval by the
     * documentation team.
     *
     * @var array<string, string>
     */
    private const MANUALS_NOT_RENDERED = [];

    /**
     * @return \Generator<string, array{string}>
     */
    public static function packageDirectoriesDataProvider(): \Generator
    {
        foreach (self::packageDirectories() as $directory) {
            yield basename($directory) => [$directory];
        }
    }

    #[DataProvider('packageDirectoriesDataProvider')]
    #[Test]
    public function linkTableNamesThePackage(string $directory): void
    {
        $packageDirectory = basename($directory);
        $extensionKey = self::composerValue($directory, 'extra', 'typo3/cms', 'extension-key');
        $this->assertNotSame('', $extensionKey, 'composer.json declares no "extra.typo3/cms.extension-key".');
        $composerName = self::composerValue($directory, 'name');
        $this->assertNotSame('', $composerName, 'composer.json declares no "name".');
        $this->assertFileExists($directory . '/README.md');

        $manual = sprintf(
            'https://docs.typo3.org/p/%s/%s/en-us/',
            $composerName,
            self::manualVersion(DocumentationGuidesTest::branch(), (string)file_get_contents($directory . '/VERSION')),
        );
        $table = self::linkTable((string)file_get_contents($directory . '/README.md'));
        if (isset(self::MANUALS_NOT_RENDERED[$packageDirectory])) {
            $manual = '-';
            $readOnline = null;
            foreach ($table as $row) {
                if (($row[0] ?? null) === '**Read online:**') {
                    $readOnline = $row[1] ?? null;
                }
            }
            if ($readOnline !== null) {
                $this->assertSame(
                    '-',
                    $readOnline,
                    sprintf(
                        'The manual of %s is listed as not rendered (%s). Remove the entry once the README links it.',
                        $packageDirectory,
                        self::MANUALS_NOT_RENDERED[$packageDirectory],
                    ),
                );
            }
        }

        $this->assertSame(
            [
                ['', 'URL'],
                ['---', '---'],
                ['**Repository:**', 'https://github.com/fgtclb/' . $packageDirectory],
                ['**Read online:**', $manual],
                ['**TER:**', 'https://extensions.typo3.org/extension/' . $extensionKey . '/'],
            ],
            $table,
        );
    }

    #[Test]
    public function everyPackageWithoutRenderedManualExists(): void
    {
        $directories = array_map('basename', self::packageDirectories());
        $this->assertSame(
            [],
            array_values(array_diff(array_keys(self::MANUALS_NOT_RENDERED), $directories)),
            'MANUALS_NOT_RENDERED names a package directory that does not exist.',
        );
    }

    /**
     * @return \Generator<string, array{string, string, string}>
     */
    public static function manualVersionDataProvider(): \Generator
    {
        yield 'main links the manual of main' => ['main', '3.0.0-dev', 'main'];
        yield 'a major version branch links the manual of the minor version' => ['2', '2.4.0-dev', '2.4'];
        yield 'a patch release keeps the manual of its minor version' => ['2', '2.4.3', '2.4'];
        yield 'a minor version branch links its own manual' => ['2.2', '2.2.5-dev', '2.2'];
        yield 'the trailing newline of VERSION is ignored' => ['2', "2.5.0-dev\n", '2.5'];
    }

    #[DataProvider('manualVersionDataProvider')]
    #[Test]
    public function manualVersionFollowsTheBranch(string $branch, string $version, string $expected): void
    {
        $this->assertSame($expected, self::manualVersion($branch, $version));
    }

    /**
     * The rows of the table that follows the first level one heading after one blank line,
     * each as its trimmed cells, a cell of dashes in the second row, the separator, as
     * `---`. Empty when the heading is not followed by a table.
     *
     * @return list<list<string>>
     */
    private static function linkTable(string $readme): array
    {
        $lines = preg_split('/\R/', $readme) ?: [];
        $heading = null;
        foreach ($lines as $number => $line) {
            if (str_starts_with($line, '# ')) {
                $heading = $number;
                break;
            }
        }
        if ($heading === null || ($lines[$heading + 1] ?? null) !== '') {
            return [];
        }
        $rows = [];
        for ($number = $heading + 2; str_starts_with($lines[$number] ?? '', '|'); $number++) {
            $rows[] = array_map('trim', explode('|', trim(trim($lines[$number]), '|')));
        }
        if (isset($rows[1])) {
            $rows[1] = array_map(
                static fn(string $cell): string => preg_match('/^-+$/', $cell) === 1 ? '---' : $cell,
                $rows[1],
            );
        }
        return $rows;
    }

    /**
     * The version of the manual docs.typo3.org renders for a branch: the name of a
     * non-numeric branch, `main`, and the major and minor version of the package for a
     * version branch.
     */
    private static function manualVersion(string $branch, string $version): string
    {
        if (preg_match('/^\d+(?:\.\d+)?$/', $branch) !== 1) {
            return $branch;
        }
        if (preg_match('/^(\d+\.\d+)\.\d+/', trim($version), $matches) !== 1) {
            throw new \RuntimeException(sprintf('"%s" is no package version.', trim($version)), 1792186801);
        }
        return $matches[1];
    }

    private static function composerValue(string $directory, string ...$path): string
    {
        $value = json_decode((string)file_get_contents($directory . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($path as $key) {
            $value = is_array($value) ? ($value[$key] ?? '') : '';
        }
        return is_string($value) ? $value : '';
    }

    /**
     * Every package below `packages/fgtclb/`.
     *
     * @return list<string>
     */
    private static function packageDirectories(): array
    {
        $directories = array_map('dirname', glob(self::repositoryPath() . '/packages/fgtclb/*/composer.json') ?: []);
        sort($directories);
        return $directories;
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
