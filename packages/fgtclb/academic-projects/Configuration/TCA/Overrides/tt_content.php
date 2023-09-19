<?php

declare(strict_types=1);

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'AcademicProjects',
        'ProjectList',
        'Academic Projects'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicprojects_projectlist'] = 'layout,recursive';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicprojects_projectlist'] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'academicprojects_projectlist',
        'FILE:EXT:academic_projects/Configuration/FlexForms/ProjectSettings.xml'
    );
})();
