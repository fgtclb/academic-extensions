<?php

use FGTCLB\AcademicProjects\Controller\ProjectController;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

    // Starting with TYPO3 v13.0 Configuration/user.tsconfig in an Extension is automatically loaded during build time
    // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Deprecation-101807-ExtensionManagementUtilityaddUserTSConfig.html
    if ($versionInformation->getMajorVersion() < 13) {
        // Allow backend users to drag and drop the new page type:
        ExtensionManagementUtility::addUserTSConfig('
            @import \'EXT:academic_projects/Configuration/user.tsconfig\'
        ');
    }

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
