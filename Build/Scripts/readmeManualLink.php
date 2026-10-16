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
 * Writes the "Read online" link in the link table of package READMEs for the branch
 * and the version "bin/set-version" applies.
 *
 *   php Build/Scripts/readmeManualLink.php [--check] <branch> <version> <package directory> [...]
 *
 * The link names the manual docs.typo3.org renders for the branch: "main" on "main",
 * and the major and minor version of <version> on a version branch ("2", "2.4"),
 * because docs.typo3.org publishes those manuals per release tag. The composer name
 * of the package comes from its composer.json. A "-" in the cell, a manual that is
 * not rendered yet, stays. ReadmeLinkTableTest of packages-dev/monorepo-shared asserts
 * the same table.
 *
 * A README whose link is already right is not touched, so a run with the version a
 * branch carries changes nothing. A changed table is padded again, because the width
 * of the link column changes with the link. "--check" changes nothing and fails for
 * every README the run would change. A README without the table, or without the row,
 * fails, before any file is written.
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
        try {
            $contents[$directory] = rewrite($directory, $manualVersion);
        } catch (\RuntimeException $exception) {
            fwrite(STDERR, $directory . '/README.md: ' . $exception->getMessage() . PHP_EOL);
            return 1;
        }
    }

    $status = 0;
    foreach ($contents as $directory => $content) {
        if ($content === null) {
            echo $directory . '/README.md: unchanged' . PHP_EOL;
            continue;
        }
        if ($check) {
            echo $directory . '/README.md: Read online is not the manual of ' . $manualVersion . PHP_EOL;
            $status = 1;
            continue;
        }
        file_put_contents($directory . '/README.md', $content);
        echo $directory . '/README.md: Read online is the manual of ' . $manualVersion . PHP_EOL;
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
 * The README of a package with the "Read online" link of the manual version, or null
 * when it already links it or says "-".
 */
function rewrite(string $directory, string $manualVersion): ?string
{
    $readme = @file_get_contents($directory . '/README.md');
    if ($readme === false) {
        throw new \RuntimeException('cannot be read');
    }
    $eol = str_contains($readme, "\r\n") ? "\r\n" : "\n";
    $lines = explode($eol, $readme);

    $heading = null;
    foreach ($lines as $number => $line) {
        if (str_starts_with($line, '# ')) {
            $heading = $number;
            break;
        }
    }
    if ($heading === null || ($lines[$heading + 1] ?? null) !== '') {
        throw new \RuntimeException('no link table one blank line below the title');
    }
    $rows = [];
    for ($number = $heading + 2; str_starts_with($lines[$number] ?? '', '|'); $number++) {
        $rows[$number] = array_map('trim', explode('|', trim(trim($lines[$number]), '|')));
    }
    if ($rows === []) {
        throw new \RuntimeException('no link table one blank line below the title');
    }
    $readOnline = null;
    foreach ($rows as $number => $cells) {
        if ($cells[0] === '**Read online:**') {
            $readOnline = $number;
        }
    }
    if ($readOnline === null) {
        throw new \RuntimeException('no "**Read online:**" row in the link table');
    }
    if (count($rows[$readOnline]) !== 2) {
        throw new \RuntimeException(sprintf('the "**Read online:**" row has %d cells, not 2', count($rows[$readOnline])));
    }
    if ($rows[$readOnline][1] === '-') {
        return null;
    }

    $manifest = json_decode((string)@file_get_contents($directory . '/composer.json'), true);
    $name = is_array($manifest) ? ($manifest['name'] ?? null) : null;
    if (!is_string($name) || $name === '') {
        throw new \RuntimeException('composer.json declares no "name"');
    }
    $link = sprintf('https://docs.typo3.org/p/%s/%s/en-us/', $name, $manualVersion);
    if ($rows[$readOnline][1] === $link) {
        return null;
    }
    $rows[$readOnline][1] = $link;

    $separator = array_key_first($rows) + 1;
    $widths = [];
    foreach ($rows as $number => $cells) {
        if ($number === $separator) {
            continue;
        }
        foreach ($cells as $column => $cell) {
            $widths[$column] = max($widths[$column] ?? 0, strlen($cell));
        }
    }
    foreach ($rows as $number => $cells) {
        $padded = [];
        foreach ($cells as $column => $cell) {
            $padded[] = $number === $separator
                ? str_repeat('-', $widths[$column] + 2)
                : ' ' . $cell . str_repeat(' ', $widths[$column] - strlen($cell)) . ' ';
        }
        $lines[$number] = '|' . implode('|', $padded) . '|';
    }
    return implode($eol, $lines);
}
