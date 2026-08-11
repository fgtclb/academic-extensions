<?php

//======================================================================================================================
// Seed the instance database from the committed template on first start-up.
//
// The path is recomputed from __DIR__ instead of being taken from "settings.php", so the instance resolves its
// database the same way inside a DDEV container and on a host stack, no matter where the repository is checked out.
//======================================================================================================================
$sqliteDatabaseTemplateFile = __DIR__ . '/../../../sqlite-databases/core-14.sqlite';
$sqliteDatabasePath = __DIR__ . '/../../var/sqlite';
$sqliteDatabaseFile = $sqliteDatabasePath . '/core-14.sqlite';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path'] = $sqliteDatabaseFile;
if (!file_exists($sqliteDatabaseFile)) {
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
