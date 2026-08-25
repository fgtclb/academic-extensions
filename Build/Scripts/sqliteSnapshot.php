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
 * Copies the SQLite database of a development instance to or from its committed
 * template.
 *
 * Invoked through the "sqlite:backup" and "sqlite:apply" composer scripts of
 * "core-13/" and "core-14/", so the working directory is the instance directory
 * and both paths are relative to it:
 *
 *   php ../Build/Scripts/sqliteSnapshot.php backup  var/sqlite/core-13.sqlite ../sqlite-databases/core-13.sqlite
 *   php ../Build/Scripts/sqliteSnapshot.php restore ../sqlite-databases/core-13.sqlite var/sqlite/core-13.sqlite
 *
 * Why this is not a plain "cp".
 *
 * SQLite may run in write ahead logging mode, and then the database file on
 * disk is not the database: the most recent transactions live in a "-wal"
 * sidecar until a checkpoint folds them back in. That checkpoint happens when
 * the last connection closes - and a backup is taken while the instance is
 * running, so there usually is one. Copying the main file alone then produces a
 * template that is silently missing the newest writes, or one that cannot be
 * opened at all.
 *
 * So a backup checkpoints first, and both directions remove the sidecars of the
 * target, which belong to the database being replaced and never to its
 * replacement. The checkpoint is harmless when the database is in any other
 * journal mode; the pragma then simply reports that there was nothing to do.
 *
 * The copy is verified by opening it and counting its tables, because a
 * truncated or half written template is worth less than no template at all.
 *
 * A backup then empties the transient tables of the copy and vacuums it. That
 * is not tidiness. A backup is taken from a running instance, and the caches
 * the composer script flushes beforehand are refilled by the very next request
 * - the flush and the copy are two steps, and this helper is usable on its own,
 * where nothing flushes anything at all. The search index is worse: a browsed
 * instance carries some ten thousand "index_*" rows the committed template has
 * none of, which is five of the eleven megabytes such a database weighs. None
 * of it is content. All of it is rebuilt by using the instance, and every byte
 * of it would land in a binary that git cannot delta compress.
 *
 * Only the copy is ever written to. The live database is read and nothing else.
 *
 * Requires nothing but PHP with pdo_sqlite: it is called from an instance whose
 * dependencies may not be installed yet.
 */
const SIDECAR_SUFFIXES = ['-wal', '-shm'];

/**
 * Tables whose name matches one of these is emptied in a snapshot.
 *
 * "cache_" is every database backed cache TYPO3 registers, "index_" is the
 * index of EXT:indexed_search, which fills up as soon as anybody walks the
 * frontend. The patterns are LIKE patterns, and the underscore is escaped
 * because LIKE would otherwise read it as "any character".
 */
const TRANSIENT_TABLE_PATTERNS = ['cache\\_%', 'index\\_%'];

/**
 * Tables that are emptied by name.
 *
 * Sessions belong to whoever was logged in when the backup was taken, locked
 * records to whatever they had open, and a processed file is a derivative that
 * lives in the git ignored "public/" tree and is regenerated on demand.
 */
const TRANSIENT_TABLES = [
    'be_sessions',
    'fe_sessions',
    'sys_file_processedfile',
    'sys_lockedrecords',
];

/**
 * @param string[] $argv
 */
function main(array $argv): int
{
    $mode = $argv[1] ?? '';
    $source = $argv[2] ?? '';
    $target = $argv[3] ?? '';

    if (!in_array($mode, ['backup', 'restore'], true) || $source === '' || $target === '') {
        fwrite(STDERR, "Usage: sqliteSnapshot.php <backup|restore> <source> <target>\n");
        return 1;
    }

    if (!extension_loaded('pdo_sqlite')) {
        fwrite(STDERR, "The pdo_sqlite extension is not available.\n");
        return 1;
    }

    if (!is_file($source)) {
        fwrite(STDERR, sprintf(
            "There is no database at \"%s\".\n%s\n",
            $source,
            $mode === 'restore'
                ? 'Nothing has been committed as a template yet, so there is nothing to restore from.'
                : 'Start the instance and set it up before backing it up.',
        ));
        return 1;
    }

    // Only a backup reads a live database. A restore reads a template that
    // nothing is writing to, and checkpointing it would only touch a file that
    // is about to be copied verbatim anyway.
    if ($mode === 'backup' && !checkpoint($source)) {
        return 1;
    }

    $targetDirectory = dirname($target);
    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
        fwrite(STDERR, sprintf("Could not create \"%s\".\n", $targetDirectory));
        return 1;
    }

    foreach (SIDECAR_SUFFIXES as $suffix) {
        $sidecar = $target . $suffix;
        if (is_file($sidecar) && !unlink($sidecar)) {
            fwrite(STDERR, sprintf("Could not remove the stale sidecar \"%s\".\n", $sidecar));
            return 1;
        }
    }

    if (!copy($source, $target)) {
        fwrite(STDERR, sprintf("Could not copy \"%s\" to \"%s\".\n", $source, $target));
        return 1;
    }

    $tables = countTables($target);
    if ($tables === null) {
        fwrite(STDERR, sprintf("The copy at \"%s\" could not be opened as a database.\n", $target));
        return 1;
    }

    // Only a backup produces an artifact that is committed. A restore writes the
    // instance, which is allowed to hold caches and a search index - it is going
    // to build them again on the next request either way.
    $removed = 0;
    if ($mode === 'backup') {
        $removed = removeTransientRows($target);
        if ($removed === null) {
            return 1;
        }
        clearstatcache(true, $target);
    }

    printf(
        "%s %s -> %s (%d tables, %s%s)\n",
        $mode === 'backup' ? 'Backed up' : 'Restored',
        $source,
        $target,
        $tables,
        formatSize((int)filesize($target)),
        $mode === 'backup' ? sprintf(', %d transient rows removed', $removed) : '',
    );

    return 0;
}

/**
 * Empties the transient tables of a snapshot and reclaims the space.
 *
 * The two file tables are handled by row rather than by table, because only
 * part of them is transient: TYPO3 indexes a file that is delivered from an
 * extension - a theme logo, say - into "sys_file" with storage 0 on first use.
 * Those rows are not seeded content, they come back by themselves, and no
 * committed template has ever held one. Files in a real storage are content and
 * are left alone.
 *
 * @return int|null the number of rows removed, or null when it could not be done
 */
function removeTransientRows(string $database): ?int
{
    try {
        $connection = new PDO('sqlite:' . $database, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $conditions = array_fill(0, count(TRANSIENT_TABLE_PATTERNS), "name LIKE ? ESCAPE '\\'");
        $conditions[] = 'name IN (' . implode(', ', array_fill(0, count(TRANSIENT_TABLES), '?')) . ')';
        $statement = $connection->prepare(sprintf(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND (%s) ORDER BY name",
            implode(' OR ', $conditions),
        ));
        $statement->execute([...TRANSIENT_TABLE_PATTERNS, ...TRANSIENT_TABLES]);

        $removed = 0;
        $emptied = [];
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $removed += (int)$connection->exec(sprintf('DELETE FROM "%s"', $table));
            $emptied[] = $table;
        }

        $removed += removeFilesOutsideOfAnyStorage($connection);
        forgetAutoincrementCounters($connection, $emptied);

        // VACUUM rewrites the file without the pages the deletes freed. It cannot
        // run inside a transaction, and PDO opens none for this.
        $connection->exec('VACUUM');
        unset($connection);
    } catch (PDOException $exception) {
        fwrite(STDERR, sprintf(
            "Could not empty the transient tables of \"%s\": %s\n",
            $database,
            $exception->getMessage(),
        ));
        return null;
    }

    return $removed;
}

/**
 * Drops the autoincrement high water marks of the tables that were emptied.
 *
 * SQLite keeps the largest uid a table has ever handed out in "sqlite_sequence",
 * and it keeps it after the last row is gone. Leaving those rows behind is
 * harmless - the tables are empty, so nothing can collide - but it would make a
 * snapshot taken from a browsed instance differ from one taken right after a
 * seed, in a table nobody thinks to look at. The point of this is that the two
 * are the same file.
 *
 * @param list<string> $tables
 */
function forgetAutoincrementCounters(PDO $connection, array $tables): void
{
    if ($tables === [] || countRows($connection, 'sqlite_sequence') === null) {
        // No table of this database uses AUTOINCREMENT, so there is no such table.
        return;
    }

    $statement = $connection->prepare('DELETE FROM sqlite_sequence WHERE name = ?');
    foreach ($tables as $table) {
        $statement->execute([$table]);
    }
}

/**
 * Removes the file records TYPO3 wrote for files that belong to no storage.
 */
function removeFilesOutsideOfAnyStorage(PDO $connection): int
{
    if (countRows($connection, 'sys_file') === null) {
        return 0;
    }

    $removed = 0;
    if (countRows($connection, 'sys_file_metadata') !== null) {
        $removed += (int)$connection->exec(
            'DELETE FROM sys_file_metadata WHERE file IN (SELECT uid FROM sys_file WHERE storage = 0)'
        );
    }
    $removed += (int)$connection->exec('DELETE FROM sys_file WHERE storage = 0');

    return $removed;
}

/**
 * @return int|null null when the table does not exist
 */
function countRows(PDO $connection, string $table): ?int
{
    try {
        $count = $connection->query(sprintf('SELECT COUNT(*) FROM "%s"', $table))?->fetchColumn();
    } catch (PDOException) {
        return null;
    }

    return is_numeric($count) ? (int)$count : null;
}

function checkpoint(string $database): bool
{
    try {
        $connection = new PDO('sqlite:' . $database, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        // TRUNCATE folds the write ahead log back into the database and empties
        // it, so the file that is copied afterwards is complete on its own.
        $connection->query('PRAGMA wal_checkpoint(TRUNCATE)');
        unset($connection);
    } catch (PDOException $exception) {
        fwrite(STDERR, sprintf("Could not checkpoint \"%s\": %s\n", $database, $exception->getMessage()));
        return false;
    }

    return true;
}

function countTables(string $database): ?int
{
    try {
        $connection = new PDO('sqlite:' . $database, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $count = $connection->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table'")?->fetchColumn();
        unset($connection);
    } catch (PDOException) {
        return null;
    }

    return is_numeric($count) ? (int)$count : null;
}

function formatSize(int $bytes): string
{
    if ($bytes >= 1048576) {
        return sprintf('%.1f MB', $bytes / 1048576);
    }

    return sprintf('%.1f kB', $bytes / 1024);
}

exit(main($argv));
