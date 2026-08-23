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
 * Switches a development instance between "seed me from the committed template"
 * and "leave me empty, I am being rebuilt".
 *
 * Invoked through the composer scripts of "core-13/" and "core-14/", so the
 * working directory is the instance directory and everything is resolved
 * relative to it. There are no path arguments on purpose: both instances then
 * declare the exact same command, and there is nothing to keep in sync when a
 * core version is added or dropped.
 *
 *   php ../Build/Scripts/instanceDatabase.php fresh
 *   php ../Build/Scripts/instanceDatabase.php seeded
 *
 * "fresh" removes the instance database and writes the marker file that
 * "config/system/additional.php" looks for. Without that marker the next
 * request would simply copy the committed template back, which is the whole
 * reason a plain "rm" is not enough.
 *
 * "seeded" removes the marker again. It runs as part of "sqlite:apply",
 * because restoring the committed template is by definition the end of a
 * rebuild - and a marker that outlives one is a trap: the instance would come
 * up empty after the next teardown, long after anybody remembers why.
 *
 * "fresh" also removes the folder the seeded files were delivered into, and that
 * is not tidiness. "composer instance:seed" writes those files itself, through
 * the FAL API - and FAL renames rather than overwrites, so a leftover
 * "profile-01.png" makes the import store "profile-01_01.png" and point its
 * "sys_file" row at it. The snapshot committed afterwards would then name a file
 * that does not exist anywhere else, and every image would be broken in a fresh
 * clone. Only the seed's own folder is touched; anything else in "fileadmin/"
 * belongs to whoever put it there.
 *
 * The marker is git-ignored and belongs to one instance, so one core version
 * can be rebuilt while the other keeps working.
 *
 * Requires nothing but PHP: it is called from an instance whose dependencies
 * may not be installed yet, and in a state where there is no database to boot
 * TYPO3 against.
 */
const MARKER_FILE = '.no-database-seed';
const DATABASE_PATTERN = 'var/sqlite/*.sqlite';
const SIDECAR_SUFFIXES = ['-wal', '-shm'];
const SEEDED_FILE_FOLDER = 'public/fileadmin/academics-seed';

/**
 * @param string[] $argv
 */
function main(array $argv): int
{
    $mode = $argv[1] ?? '';

    if (!in_array($mode, ['fresh', 'seeded'], true)) {
        fwrite(STDERR, "Usage: instanceDatabase.php <fresh|seeded>\n");
        return 1;
    }

    // Guard against being run from anywhere else. Every path below is relative
    // to the working directory, and "fresh" deletes files.
    if (!is_file('config/system/additional.php')) {
        fwrite(STDERR, sprintf(
            "\"%s\" is not a development instance directory.\n"
            . "Run this through \"composer instance:fresh\" from \"core-13/\" or \"core-14/\".\n",
            getcwd() ?: '.',
        ));
        return 1;
    }

    if ($mode === 'seeded') {
        return removeMarker();
    }

    foreach (databaseFiles() as $database) {
        foreach (['', ...SIDECAR_SUFFIXES] as $suffix) {
            $file = $database . $suffix;
            if (is_file($file) && !unlink($file)) {
                fwrite(STDERR, sprintf("Could not remove \"%s\".\n", $file));
                return 1;
            }
        }
        printf("Removed %s\n", $database);
    }

    if (!removeSeededFiles()) {
        return 1;
    }

    if (!is_file(MARKER_FILE) && file_put_contents(MARKER_FILE, markerContents()) === false) {
        fwrite(STDERR, sprintf("Could not write \"%s\".\n", MARKER_FILE));
        return 1;
    }

    printf(
        "Wrote %s - this instance will not be seeded from its committed template any more.\n\n"
        . "Next:\n"
        . "  1. start it, then set the database up:  composer system:refresh\n"
        . "  2. create a backend user:               vendor/bin/typo3 backend:user:create\n"
        . "  3. put content in, by hand or with a generator\n"
        . "  4. commit the result:                   composer sqlite:backup\n"
        . "  5. back to normal:                      composer sqlite:apply\n",
        MARKER_FILE,
    );

    return 0;
}

function removeMarker(): int
{
    if (!is_file(MARKER_FILE)) {
        return 0;
    }

    if (!unlink(MARKER_FILE)) {
        fwrite(STDERR, sprintf("Could not remove \"%s\".\n", MARKER_FILE));
        return 1;
    }

    printf("Removed %s - this instance is seeded from its committed template again.\n", MARKER_FILE);

    return 0;
}

/**
 * Removes the folder "config/system/additional.php" delivers the committed seed
 * files into, so the rebuild starts with an empty one.
 */
function removeSeededFiles(): bool
{
    if (!is_dir(SEEDED_FILE_FOLDER)) {
        return true;
    }

    $entries = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(SEEDED_FILE_FOLDER, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($entries as $entry) {
        $removed = $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        if (!$removed) {
            fwrite(STDERR, sprintf("Could not remove \"%s\".\n", $entry->getPathname()));
            return false;
        }
    }

    if (!rmdir(SEEDED_FILE_FOLDER)) {
        fwrite(STDERR, sprintf("Could not remove \"%s\".\n", SEEDED_FILE_FOLDER));
        return false;
    }

    printf("Removed %s\n", SEEDED_FILE_FOLDER);

    return true;
}

/**
 * @return string[]
 */
function databaseFiles(): array
{
    return array_values(array_filter(glob(DATABASE_PATTERN) ?: [], 'is_file'));
}

function markerContents(): string
{
    return <<<TEXT
    This instance is being rebuilt from nothing.

    While this file exists, "config/system/additional.php" does not copy the
    committed template from "sqlite-databases/" into "var/sqlite/", nor the
    committed seed files from "packages-dev/dev-site/Resources/Public/SeedFiles/"
    into "public/fileadmin/", so the instance stays empty until it is set up.

    It is git-ignored and must never be committed. "composer sqlite:apply"
    removes it, and so does deleting it by hand.

    TEXT;
}

exit(main($argv));
