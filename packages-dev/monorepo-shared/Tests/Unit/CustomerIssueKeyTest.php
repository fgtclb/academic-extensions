<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The repository is public, and no file in it names an issue of a customer project. Such a
 * key tells the reader which customer reported what, and it leads nowhere: the trackers
 * behind it are private. The ACE issue a change belongs to is the reference to give
 * (ACE-753).
 *
 * The customer projects are not listed here, because the list would name them. Every
 * reference of the shape `ABC-NNN`, two to five capital letters and a number, is refused
 * instead, unless its prefix is one of the few known ones below: the ACE project, and
 * licence, standard and advisory names. A new one of those goes into the list, a customer
 * key never does. A prefix of six letters or more is not checked, and none exists today.
 *
 * Only the sources are read: the directories and root files below, text files only. What
 * composer, npm, the documentation renderer, the test bootstrap and the development instances
 * write is left out, and so are the machine-local `*.local.*` files, the git-ignored working
 * directory `.agent/` and the committed binaries such as the instance database templates.
 * A symbolic link is read through its target only.
 */
final class CustomerIssueKeyTest extends TestCase
{
    /**
     * Prefixes that do not belong to a customer project: the ACE project of this repository,
     * licences (`GPL-2.0`, `LGPL-2.1`, `AGPL-3.0`, `BSD-3`, `MIT-0`), standards (`PSR-14`,
     * `UTF-8`, `ISO-8859`, `SHA-1`), the `CISO-2026` of the EU declarations of conformity,
     * and advisories (`CVE-2026`, `TYPO3-CORE-SA-2026`, `TYPO3-PSA-2026`).
     */
    private const KNOWN_PREFIXES = [
        'ACE', 'AGPL', 'BSD', 'CISO', 'CVE', 'GPL', 'ISO', 'LGPL', 'MIT', 'PSA', 'PSR', 'SA', 'SHA', 'UTF',
    ];

    private const ROOT_DOT_FILES = ['.editorconfig', '.gitattributes', '.gitignore'];

    private const DIRECTORIES = [
        '.github',
        'Build',
        'bin',
        'core-13/config',
        'core-14/config',
        'docs',
        'openspec',
        'packages',
        'packages-dev',
    ];

    private const SKIPPED_DIRECTORIES = [
        '.Build',
        '.cache',
        'Documentation-GENERATED-temp',
        'additional',
        'autoload-tests',
        'documentation-rendered',
        'node_modules',
        'var',
        'vendor',
    ];

    /**
     * An empty entry stands for files without an extension, such as the scripts in `bin/`.
     */
    private const EXTENSIONS = [
        '', 'css', 'csv', 'editorconfig', 'gitattributes', 'gitignore', 'html', 'js', 'json', 'md',
        'mjs', 'mts', 'neon', 'patch', 'php', 'rst', 'scss', 'sh', 'sql', 'ts', 'tsconfig', 'txt',
        'typoscript', 'xlf', 'xml', 'yaml', 'yml',
    ];

    #[Test]
    public function noSourceFileNamesACustomerIssueKey(): void
    {
        $found = [];
        foreach ($this->sourceFiles() as $file) {
            $lines = file($file) ?: [];
            foreach ($lines as $number => $line) {
                $keys = $this->unknownKeys($line);
                if ($keys !== []) {
                    $found[] = sprintf('%s:%d %s', $this->relative($file), $number + 1, implode(', ', $keys));
                }
            }
        }
        sort($found);

        $this->assertSame(
            [],
            $found,
            'Name the ACE issue instead of a customer issue. A licence, standard or advisory name goes into KNOWN_PREFIXES.',
        );
    }

    /**
     * The two unknown keys are put together at run time, or the scan would find them here.
     */
    #[Test]
    public function onlyAKeyWithAnUnknownPrefixIsReported(): void
    {
        $first = 'XYZ' . '-12';
        $second = 'AB' . '-3';

        $this->assertSame(
            [$first, $second],
            $this->unknownKeys(
                'ACE-753, ' . $first . ', GPL-2.0-or-later, UTF-8, ' . $second . ', TYPO3-CORE-SA-2026-006, D-026, Z0-9'
            ),
        );
    }

    /**
     * The scan reaches the files it is meant to read and none of the installed, generated or
     * machine-local ones, so a scan that finds nothing cannot be mistaken for a clean
     * repository.
     */
    #[Test]
    public function theScanReadsTheSources(): void
    {
        $files = array_map($this->relative(...), iterator_to_array($this->sourceFiles(), false));

        $this->assertContains('packages-dev/monorepo-shared/Tests/Unit/CustomerIssueKeyTest.php', $files);
        $this->assertContains('AGENTS.md', $files);
        $this->assertContains('.gitignore', $files);
        $this->assertContains('bin/release', $files);
        $this->assertContains('core-14/config/system/settings.php', $files);
        $this->assertContains('docs/Index.md', $files);
        $this->assertNotContains('CLAUDE.md', $files);
        $this->assertNotContains('AGENTS.local.md', $files);
        $this->assertSame(
            [],
            array_values(array_filter(
                $files,
                static fn(string $file): bool => preg_match(
                    '#(^|/)(\.Build|\.cache|node_modules|vendor|var|autoload-tests)/#',
                    $file
                ) === 1,
            )),
        );
    }

    /**
     * @return \Generator<int, string>
     */
    private function sourceFiles(): \Generator
    {
        $root = self::repositoryPath();
        $rootFiles = glob($root . '/*', GLOB_NOSORT) ?: [];
        foreach (self::ROOT_DOT_FILES as $dotFile) {
            $rootFiles[] = $root . '/' . $dotFile;
        }
        foreach ($rootFiles as $file) {
            if (is_file($file) && $this->isSource($file)) {
                yield $file;
            }
        }
        foreach (self::DIRECTORIES as $directory) {
            if (!is_dir($root . '/' . $directory)) {
                continue;
            }
            $files = new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator($root . '/' . $directory, \FilesystemIterator::SKIP_DOTS),
                    static fn(\SplFileInfo $file): bool => !$file->isDir()
                        || !in_array($file->getFilename(), self::SKIPPED_DIRECTORIES, true),
                ),
            );
            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->isFile() && $this->isSource($file->getPathname())) {
                    yield $file->getPathname();
                }
            }
        }
    }

    /**
     * @return string[] The references of the shape `ABC-NNN` whose prefix is not known.
     */
    private function unknownKeys(string $line): array
    {
        if (preg_match_all('/\b([A-Z]{2,5})-\d+\b/', $line, $matches, PREG_SET_ORDER) === 0) {
            return [];
        }
        $keys = [];
        foreach ($matches as $match) {
            if (!in_array($match[1], self::KNOWN_PREFIXES, true)) {
                $keys[] = $match[0];
            }
        }
        return $keys;
    }

    /**
     * A text file of a listed type that is neither a link nor machine-local. A file that
     * carries a null byte in its first kilobytes is taken for a binary.
     */
    private function isSource(string $file): bool
    {
        if (is_link($file) || str_contains(basename($file), '.local.')) {
            return false;
        }
        if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
            return false;
        }
        $head = file_get_contents($file, false, null, 0, 8192);
        return $head !== false && !str_contains($head, "\0");
    }

    private function relative(string $file): string
    {
        return substr($file, strlen(self::repositoryPath()) + 1);
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
