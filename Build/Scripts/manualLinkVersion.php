<?php

declare(strict_types=1);

/*
 * This file is part of the fgtclb/academic extension collection.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Writes the manual version of every absolute link from the files of a package into a
 * manual of this repository, for the branch and the version "bin/set-version" applies.
 *
 *   php Build/Scripts/manualLinkVersion.php [--check] <branch> <version> <package directory> [...]
 *
 * A link has the form "https://docs.typo3.org/p/<composer name>/<manual version>/en-us/",
 * and a manual of this repository is one whose composer name the composer.json of a
 * package next to <package directory> declares. The manual version is the one
 * readmeManualLink.php writes into the README: "main" on "main", and the major and minor
 * version of <version> on a version branch ("2", "2.4"). PackageManualLinkTest of
 * packages-dev/monorepo-shared asserts the same links.
 *
 * Scheme, host and composer name are matched in any letter case. In the README of the
 * package, the lines of its link table, the lines that start with "|", are left to
 * readmeManualLink.php, which writes the "Read online" link and pads the table. Left out,
 * as by that test: every "Documentation/Changelog/" folder and every
 * "Documentation/Upgrade/" page, which are shared between the branches, the render output,
 * "vendor/" and "node_modules/" trees and binary files.
 *
 * A file whose links are already right is not touched, so a run with the version a branch
 * carries changes nothing. "--check" changes nothing and fails for every file the run
 * would change. A link into such a manual without a manual version and a locale fails,
 * and so does a package directory that cannot be read, before any file is written.
 */
exit(main($argv));

/**
 * @param list<string> $argv
 */
function main(array $argv): int
{
    $arguments = array_slice($argv, 1);
    $check = ($arguments[0] ?? '') === '--check';
    if ($check) {
        array_shift($arguments);
    }
    if (count($arguments) < 3) {
        fwrite(STDERR, 'Usage: php ' . $argv[0] . ' [--check] <branch> <version> <package directory> [...]' . PHP_EOL);
        return 2;
    }
    [$branch, $version] = $arguments;
    $manualVersion = manualVersion($branch, $version);
    if ($manualVersion === null) {
        fwrite(STDERR, sprintf('No manual version for branch "%s" and version "%s".', $branch, $version) . PHP_EOL);
        return 1;
    }

    $contents = [];
    foreach (array_slice($arguments, 2) as $directory) {
        $directory = rtrim($directory, '/');
        if (!is_dir($directory)) {
            fwrite(STDERR, $directory . ': cannot be read' . PHP_EOL);
            return 1;
        }
        $pattern = linkPattern(composerNames(dirname($directory)));
        foreach (linkingFiles($directory) as $file) {
            try {
                $content = rewrite(
                    (string)file_get_contents($file),
                    $pattern,
                    $manualVersion,
                    $file === $directory . '/README.md',
                );
            } catch (\RuntimeException $exception) {
                fwrite(STDERR, $file . ': ' . $exception->getMessage() . PHP_EOL);
                return 1;
            }
            if ($content !== null) {
                $contents[$directory][$file] = $content;
            }
        }
        $contents[$directory] ??= [];
    }

    $status = 0;
    foreach ($contents as $directory => $files) {
        if ($files === []) {
            echo $directory . ': manual links unchanged' . PHP_EOL;
            continue;
        }
        foreach ($files as $file => $content) {
            if ($check) {
                echo $file . ': links a manual other than ' . $manualVersion . PHP_EOL;
                $status = 1;
                continue;
            }
            file_put_contents($file, $content);
            echo $file . ': links the manual of ' . $manualVersion . PHP_EOL;
        }
    }
    return $status;
}

/**
 * The version of the manual docs.typo3.org renders for a branch: the name of a
 * non-numeric branch, and the major and minor version for a version branch.
 */
function manualVersion(string $branch, string $version): ?string
{
    if ($branch === '') {
        return null;
    }
    if (preg_match('/^\d+(?:\.\d+)?$/', $branch) !== 1) {
        return $branch;
    }
    if (preg_match('/^(\d+\.\d+)\.\d+/', trim($version), $matches) !== 1) {
        return null;
    }
    return $matches[1];
}

/**
 * The composer names of the packages in a directory, the longest first, so that the
 * name of a package is never taken for the start of a longer one.
 *
 * @return list<string>
 */
function composerNames(string $packagesDirectory): array
{
    $names = [];
    foreach (glob($packagesDirectory . '/*/composer.json') ?: [] as $file) {
        $manifest = json_decode((string)file_get_contents($file), true);
        $name = is_array($manifest) ? ($manifest['name'] ?? null) : null;
        if (is_string($name) && $name !== '') {
            $names[] = $name;
        }
    }
    usort($names, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    return $names;
}

/**
 * The pattern of a link into one of the manuals: the part up to the composer name, the
 * manual version and the rest up to the end of the locale. Version and locale are
 * optional, so that a link without them is found and reported.
 *
 * @param list<string> $names
 */
function linkPattern(array $names): string
{
    return sprintf(
        '~(https?://docs\.typo3\.org/p/(?:%s)/)(?:([\w.-]+)(/[a-z]{2}-[a-z]{2}(?![\w-])))?~i',
        implode('|', array_map(static fn(string $name): string => preg_quote($name, '~'), $names)),
    );
}

/**
 * The files of a package whose links are written.
 *
 * @return list<string>
 */
function linkingFiles(string $directory): array
{
    $files = new \RecursiveIteratorIterator(
        new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            static fn(\SplFileInfo $file): bool => !$file->isDir()
                || !in_array($file->getFilename(), ['Documentation-GENERATED-temp', 'node_modules', 'vendor'], true),
        ),
    );
    $linking = [];
    /** @var \SplFileInfo $file */
    foreach ($files as $file) {
        $relative = substr($file->getPathname(), strlen($directory) + 1);
        if (str_starts_with($relative, 'Documentation/Changelog/')
            || str_starts_with($relative, 'Documentation/Upgrade/')
            || str_contains((string)file_get_contents($file->getPathname()), "\0")
        ) {
            continue;
        }
        $linking[] = $file->getPathname();
    }
    sort($linking);
    return $linking;
}

/**
 * The content with every link naming the manual version, or null when it already does.
 * The lines that start with "|" keep their links when $skipTableRows is set.
 */
function rewrite(string $content, string $pattern, string $manualVersion, bool $skipTableRows): ?string
{
    $rewritten = preg_replace_callback(
        $pattern,
        static function (array $match) use ($content, $manualVersion, $skipTableRows): string {
            $lineStart = strrpos(substr($content, 0, $match[0][1]), "\n");
            if ($skipTableRows && ($content[$lineStart === false ? 0 : $lineStart + 1] ?? '') === '|') {
                return $match[0][0];
            }
            if ($match[2][0] === null) {
                throw new \RuntimeException(sprintf(
                    'line %d: "%s" names no manual version and locale',
                    substr_count($content, "\n", 0, $match[0][1]) + 1,
                    $match[0][0],
                ));
            }
            return $match[1][0] . $manualVersion . $match[3][0];
        },
        $content,
        flags: PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL,
    );
    if ($rewritten === null) {
        throw new \RuntimeException('cannot be scanned for manual links');
    }
    return $rewritten === $content ? null : $rewritten;
}
