<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every absolute link from a file of a package into a manual of this repository names the
 * manual of the version line the file ships with.
 *
 * A link has the form `https://docs.typo3.org/p/<composer name>/<manual version>/en-us/...`,
 * and a manual of this repository is one whose composer name a `composer.json` below
 * `packages/fgtclb/` declares, `fgtclb/category-types` included. The manual version is the
 * one {@see ReadmeLinkTableTest::manualVersion()} maps the branch and the package `VERSION`
 * to: `main` on `main`, the major and minor version on a version branch (`2.4` on `2`).
 * Branch `2` was cut from `main` with links into the manuals of `main`, which describe 3.0,
 * and nothing reported them until ACE-941. `Build/Scripts/manualLinkVersion.php` writes
 * the version, and `bin/set-version` runs it, so a minor version bump on a version branch
 * and a branch cut keep this test green.
 *
 * A link into such a manual that names no manual version and locale fails too, because
 * no version can be written into it. Scheme, host and composer name are matched in any
 * letter case.
 *
 * The `README.md` of a package is read without its link table, the lines that start with
 * `|`. The table is {@see ReadmeLinkTableTest}'s, and `Build/Scripts/readmeManualLink.php`
 * writes it and pads it, which a rewrite of the link alone would leave unpadded. A link
 * in the text of the README is checked here.
 *
 * Left out:
 *
 * - Every `Documentation/Changelog/` folder and every `Documentation/Upgrade/` page. They
 *   are shared between the branches: an unreleased changelog folder and the upgrade page
 *   of its release are byte-identical on `main` and the version branch, and a released
 *   changelog folder is never edited. No single manual version is right on every branch
 *   that ships them.
 * - What is not a source: the render output `Documentation-GENERATED-temp/`, every
 *   `vendor/` and `node_modules/` tree, and binary files.
 */
final class PackageManualLinkTest extends TestCase
{
    private const SKIPPED_DIRECTORIES = ['Documentation-GENERATED-temp', 'node_modules', 'vendor'];

    /**
     * Paths relative to the package that are left out, a directory with its trailing slash.
     */
    private const SKIPPED_PATHS = ['Documentation/Changelog/', 'Documentation/Upgrade/'];

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
    public function linksNameTheManualOfTheBranch(string $directory): void
    {
        $manualVersion = ReadmeLinkTableTest::manualVersion(
            DocumentationGuidesTest::branch(),
            (string)file_get_contents($directory . '/VERSION'),
        );
        $names = self::composerNames();
        $wrong = [];
        foreach (self::linkingFiles($directory) as $file) {
            $links = self::manualLinks((string)file_get_contents($file), $names, $file === $directory . '/README.md');
            foreach ($links as [$line, $link, $name, $version]) {
                if ($version === $manualVersion) {
                    continue;
                }
                $wrong[] = sprintf(
                    '%s:%d %s, expected %s',
                    self::relativePath($file),
                    $line,
                    $link,
                    $version === null
                        ? sprintf('https://docs.typo3.org/p/%s/%s/en-us/...', $name, $manualVersion)
                        : str_replace('/' . $name . '/' . $version . '/', '/' . $name . '/' . $manualVersion . '/', $link),
                );
            }
        }

        $this->assertSame(
            [],
            $wrong,
            'Link the manual of the branch. Build/Scripts/manualLinkVersion.php writes it, see docs/workflow/releasing.md.',
        );
    }

    #[Test]
    public function theScanFindsEveryLinkIntoAManualOfTheRepository(): void
    {
        $content = implode("\n", [
            '# https://docs.typo3.org/p/fgtclb/academic-jobs/main/en-us/Configuration/Validations/Index.html',
            'See `the page <https://docs.typo3.org/p/fgtclb/academic-persons-edit/2.4/en-us/Index.html#anchor>`__ and',
            '[the manual](https://docs.typo3.org/p/fgtclb/category-types/main/en-us/), '
                . 'https://docs.typo3.org/p/fgtclb/academic-persons/2.3/en-us',
            'Not here: https://docs.typo3.org/p/fgtclb/academic-persons-sync-other/main/en-us/,',
            'https://docs.typo3.org/p/georgringer/news/main/en-us/, https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/',
            'and http://docs.typo3.org/p/fgtclb/academic-jobs/1.0/en-us/Index.html',
            'Without a version: https://docs.typo3.org/p/fgtclb/academic-jobs/en-us/Index.html',
            'HTTPS://DOCS.TYPO3.ORG/P/FGTCLB/ACADEMIC-JOBS/2.4/EN-US/',
            '| **Read online:** | https://docs.typo3.org/p/fgtclb/academic-jobs/2.4/en-us/ |',
        ]);

        $this->assertSame(
            [
                [
                    1,
                    'https://docs.typo3.org/p/fgtclb/academic-jobs/main/en-us/Configuration/Validations/Index.html',
                    'fgtclb/academic-jobs',
                    'main',
                ],
                [
                    2,
                    'https://docs.typo3.org/p/fgtclb/academic-persons-edit/2.4/en-us/Index.html#anchor',
                    'fgtclb/academic-persons-edit',
                    '2.4',
                ],
                [3, 'https://docs.typo3.org/p/fgtclb/category-types/main/en-us/', 'fgtclb/category-types', 'main'],
                [3, 'https://docs.typo3.org/p/fgtclb/academic-persons/2.3/en-us', 'fgtclb/academic-persons', '2.3'],
                [6, 'http://docs.typo3.org/p/fgtclb/academic-jobs/1.0/en-us/Index.html', 'fgtclb/academic-jobs', '1.0'],
                [7, 'https://docs.typo3.org/p/fgtclb/academic-jobs/en-us/Index.html', 'fgtclb/academic-jobs', null],
                [8, 'HTTPS://DOCS.TYPO3.ORG/P/FGTCLB/ACADEMIC-JOBS/2.4/EN-US/', 'FGTCLB/ACADEMIC-JOBS', '2.4'],
                [9, 'https://docs.typo3.org/p/fgtclb/academic-jobs/2.4/en-us/', 'fgtclb/academic-jobs', '2.4'],
            ],
            self::manualLinks($content, self::composerNames(), false),
        );
        $this->assertCount(7, self::manualLinks($content, self::composerNames(), true), 'A table row of a README is left out.');
    }

    /**
     * The scan reaches the files it is meant to read and none it leaves out, so a scan that
     * finds nothing cannot be mistaken for a repository without wrong links.
     */
    #[Test]
    public function theScanReadsThePackageFiles(): void
    {
        $this->assertContains('fgtclb/category-types', self::composerNames());
        $this->assertCount(count(self::packageDirectories()), self::composerNames());

        $directory = self::repositoryPath() . '/packages/fgtclb/academic-jobs';
        $files = array_map(
            static fn(string $file): string => substr($file, strlen($directory) + 1),
            self::linkingFiles($directory),
        );
        $this->assertContains('Configuration/AcademicJobs/Settings.yaml', $files);
        $this->assertContains('README.md', $files);
        $this->assertSame(
            [],
            array_values(array_filter(
                $files,
                static fn(string $file): bool => preg_match('#^Documentation/(Changelog|Upgrade)/#', $file) === 1,
            )),
        );
        $this->assertNotSame(
            [],
            self::manualLinks(
                (string)file_get_contents($directory . '/Configuration/AcademicJobs/Settings.yaml'),
                self::composerNames(),
                false,
            ),
        );
    }

    /**
     * The links into a manual of the repository, each as its line, the link up to the
     * character that ends it in text, reStructuredText, Markdown or YAML, the composer
     * name and the manual version, which is null for a link without a manual version and
     * a locale. The lines that start with `|` are left out when $skipTableRows is set.
     *
     * @param list<string> $names
     * @return list<array{int, string, string, ?string}>
     */
    private static function manualLinks(string $content, array $names, bool $skipTableRows): array
    {
        usort($names, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        $pattern = sprintf(
            '~https?://docs\.typo3\.org/p/(%s)/(?:([\w.-]+)/[a-z]{2}-[a-z]{2}(?![\w-]))?[^\s<>"\'`()\[\]]*~i',
            implode('|', array_map(static fn(string $name): string => preg_quote($name, '~'), $names)),
        );
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);
        $links = [];
        $lines = explode("\n", $content);
        foreach ($matches as $match) {
            $line = substr_count($content, "\n", 0, (int)$match[0][1]) + 1;
            if ($skipTableRows && str_starts_with($lines[$line - 1], '|')) {
                continue;
            }
            $links[] = [
                $line,
                (string)$match[0][0],
                (string)$match[1][0],
                $match[2][0] ?? null,
            ];
        }
        return $links;
    }

    /**
     * The files of a package that are read, the ones left out excepted.
     *
     * @return list<string>
     */
    private static function linkingFiles(string $directory): array
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                static fn(\SplFileInfo $file): bool => !$file->isDir() || !in_array($file->getFilename(), self::SKIPPED_DIRECTORIES, true),
            ),
        );
        $linking = [];
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $relative = substr($file->getPathname(), strlen($directory) + 1);
            foreach (self::SKIPPED_PATHS as $skipped) {
                if ($relative === $skipped || (str_ends_with($skipped, '/') && str_starts_with($relative, $skipped))) {
                    continue 2;
                }
            }
            if (str_contains((string)file_get_contents($file->getPathname()), "\0")) {
                continue;
            }
            $linking[] = $file->getPathname();
        }
        sort($linking);
        return $linking;
    }

    /**
     * The composer names of the packages below `packages/fgtclb/`.
     *
     * @return list<string>
     */
    private static function composerNames(): array
    {
        $names = [];
        foreach (self::packageDirectories() as $directory) {
            $manifest = json_decode((string)file_get_contents($directory . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            $name = is_array($manifest) ? ($manifest['name'] ?? null) : null;
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }
        return $names;
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

    private static function relativePath(string $file): string
    {
        return substr($file, strlen(self::repositoryPath()) + 1);
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
