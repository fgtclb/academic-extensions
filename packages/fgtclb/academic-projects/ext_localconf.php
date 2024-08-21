<?php

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
        @import \'EXT:academic_projects/Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig\'
        @import \'EXT:academic_projects/Configuration/TsConfig/Page/Tceform/Pages.tsconfig\'
    ');

    $projectDokType = \FGTCLB\AcademicProjects\Domain\Enumeration\Page::TYPE_ACEDEMIC_PROJECT;
    // Allow backend users to drag and drop the new page type:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
        sprintf(
            'options.pageTree.doktypesToShowInNewPageDragArea := addToList(%d)',
            $projectDokType
        )
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'AcademicProjects',
        'ProjectList',
        [
            \FGTCLB\AcademicProjects\Controller\ProjectController::class => 'list',
        ],
        [
            \FGTCLB\AcademicProjects\Controller\ProjectController::class => 'list',
        ]
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'AcademicProjects',
        'ProjectListSingle',
        [
            \FGTCLB\AcademicProjects\Controller\ProjectController::class => 'list',
        ],
        [
            \FGTCLB\AcademicProjects\Controller\ProjectController::class => 'list',
        ]
    );
})();
