<?php

//======================================================================================================================
// Seed the instance database from the committed template on first start-up.
//
// The path is recomputed from __DIR__ instead of being taken from "settings.php", so the instance resolves its
// database the same way inside a DDEV container and on a host stack, no matter where the repository is checked out.
//
// The seed is suppressed while the instance is being rebuilt from nothing, because a plain "rm" of the database would
// otherwise just be undone by the next request. Either switch does it, both are git-ignored and never committed:
//
//   * the marker file ".no-database-seed" in the instance directory, written by "composer instance:fresh"
//   * the environment variable "ACADEMICS_NO_DATABASE_SEED", set to anything but "" or "0"
//
// Without one of them nothing changes: a missing database is still replaced by the committed template. See
// "docs/development/environment.md", section "Rebuilding an instance from nothing".
//======================================================================================================================
$sqliteDatabaseTemplateFile = __DIR__ . '/../../../sqlite-databases/core-12.sqlite';
$sqliteDatabasePath = __DIR__ . '/../../var/sqlite';
$sqliteDatabaseFile = $sqliteDatabasePath . '/core-12.sqlite';
$sqliteDatabaseSeedMarkerFile = __DIR__ . '/../../.no-database-seed';
$sqliteDatabaseSeedEnvironment = (string)getenv('ACADEMICS_NO_DATABASE_SEED');
$sqliteDatabaseSeedSuppressed = file_exists($sqliteDatabaseSeedMarkerFile)
    || ($sqliteDatabaseSeedEnvironment !== '' && $sqliteDatabaseSeedEnvironment !== '0');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path'] = $sqliteDatabaseFile;
if (!$sqliteDatabaseSeedSuppressed && !file_exists($sqliteDatabaseFile)) {
    @mkdir($sqliteDatabasePath, 0775, true);
    @copy($sqliteDatabaseTemplateFile, $sqliteDatabaseFile);
}
//======================================================================================================================
// Deliver the seed files into "fileadmin/" on first start-up.
//
// The committed template above carries "sys_file" rows, and a "sys_file" row without its file is a broken image on
// every page that references it. Those files cannot be committed inside this instance - "public/" is git-ignored. They
// are committed in the seed package instead, in a tree that mirrors "fileadmin/" one to one, and are copied out of it
// here: the same "seed what is missing" rule the database follows, so a database and the files it points at always
// arrive together.
//
// The check costs one "is_dir()" per request, and the copy runs only when the seeded folder is absent - on a fresh
// clone, and again when somebody empties "fileadmin/" by hand. An existing file is never overwritten: what an editor
// uploaded into this instance is theirs.
//
// The same switches suppress it that suppress the database seeding, and for a stronger reason than symmetry: while an
// instance is rebuilt from nothing, "composer instance:seed" writes these files itself through the FAL API. A file
// already sitting in the target folder would make it store a renamed copy ("profile-01_01.png"), and the snapshot
// taken afterwards would reference a name that does not exist in a fresh clone.
//
// The files are drawn by "Build/Scripts/generateSeedFiles.php". See "docs/development/environment.md", section
// "Seed files, and how they reach an instance".
//======================================================================================================================
$seedFilesPath = __DIR__ . '/../../../packages-dev/dev-site/Resources/Public/SeedFiles';
$seedFilesTargetPath = __DIR__ . '/../../public/fileadmin';
$seedFilesMarkerPath = $seedFilesTargetPath . '/academics-seed';
if (!$sqliteDatabaseSeedSuppressed && is_dir($seedFilesPath) && !is_dir($seedFilesMarkerPath)) {
    $seedFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($seedFilesPath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($seedFiles as $seedFile) {
        $seedFileTarget = $seedFilesTargetPath . substr($seedFile->getPathname(), strlen($seedFilesPath));
        if ($seedFile->isDir()) {
            @mkdir($seedFileTarget, 0775, true);
        } elseif (!file_exists($seedFileTarget)) {
            @mkdir(dirname($seedFileTarget), 0775, true);
            @copy($seedFile->getPathname(), $seedFileTarget);
        }
    }
}
//======================================================================================================================

//======================================================================================================================

//======================================================================================================================
// Include additional files from subfolder `additional/`.
//
// The folder is git-ignored and is the place for local-only overrides, for example when the instance is served by a
// host LAMP stack instead of DDEV and needs different binary paths or a different mail transport.
//======================================================================================================================
$additionalIncludePath = __DIR__ . '/additional';
$additionalIncludeFilePattern = $additionalIncludePath . '/*.php';
if (is_dir($additionalIncludePath)) {
    $files = glob($additionalIncludeFilePattern);
    foreach ($files as $file) {
        include $file;
    }
}
//======================================================================================================================

// The instance is reached under several host names (DDEV, host stack), so no host name is pinned here.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
