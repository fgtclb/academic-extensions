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
})();
