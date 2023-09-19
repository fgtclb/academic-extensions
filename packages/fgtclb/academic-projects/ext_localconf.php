<?php

(static function (): void {
    $projectDokType = \FGTCLB\AcademicProjects\Domain\Enumeration\Page::TYPE_EDUCATIONAL_PROJECT;
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
})();
