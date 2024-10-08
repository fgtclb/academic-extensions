<?php

use FGTCLB\AcademicProjects\Controller\ProjectController;
use FGTCLB\AcademicProjects\Enumeration\PageTypes;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    ExtensionManagementUtility::addPageTSConfig('
        @import \'EXT:academic_projects/Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig\'
        @import \'EXT:academic_projects/Configuration/TsConfig/Page/Tceform/Pages.tsconfig\'
    ');

    $projectDokType = PageTypes::TYPE_ACEDEMIC_PROJECT;
    // Allow backend users to drag and drop the new page type:
    ExtensionManagementUtility::addUserTSConfig(
        sprintf(
            'options.pageTree.doktypesToShowInNewPageDragArea := addToList(%d)',
            $projectDokType
        )
    );

    ExtensionUtility::configurePlugin(
        'AcademicProjects',
        'ProjectList',
        [
            ProjectController::class => 'list',
        ],
        [
            ProjectController::class => 'list',
        ]
    );

    ExtensionUtility::configurePlugin(
        'AcademicProjects',
        'ProjectListSingle',
        [
            ProjectController::class => 'list',
        ],
        [
            ProjectController::class => 'list',
        ]
    );
})();
