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
