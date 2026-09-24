<?php

use FGTCLB\AcademicProjects\Controller\ProjectController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    ExtensionUtility::configurePlugin(
        'AcademicProjects',
        'ProjectList',
        [
            ProjectController::class => 'list',
        ],
        [
            ProjectController::class => 'list',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'AcademicProjects',
        'ProjectListSingle',
        [
            ProjectController::class => 'list',
        ],
        [
            ProjectController::class => 'list',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    // The list actions are not cacheable, so the cached page around them never depends on
    // the demand. Kept out of the cache hash, every filter URL of a list shares that one page
    // cache entry, rather than the redirect of a filter submission signing one entry per
    // combination of categories and sorting that anybody cares to submit.
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_academicprojects_projectlist[demand]';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_academicprojects_projectlistsingle[demand]';
})();
