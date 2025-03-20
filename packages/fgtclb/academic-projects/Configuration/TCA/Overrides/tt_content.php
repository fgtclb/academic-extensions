<?php

declare(strict_types=1);

(static function (): void {
    $ll = fn(string $langKey): string => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_db.xlf:' . $langKey;

    // Configure list plugin

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'AcademicProjects',
        'ProjectList',
        $ll('tx_academic_projects_p1.name'),
        'actions-code-merge'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicprojects_projectlist'] = 'layout,recursive';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicprojects_projectlist'] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'academicprojects_projectlist',
        'FILE:EXT:academic_projects/Configuration/FlexForms/ProjectSettings.xml'
    );

    // Configure single plugin

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'AcademicProjects',
        'ProjectListSingle',
        $ll('tx_academic_projects_p2.name'),
        'actions-code-merge'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicprojects_projectlistsingle'] = 'layout,recursive';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicprojects_projectlistsingle'] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'academicprojects_projectlistsingle',
        'FILE:EXT:academic_projects/Configuration/FlexForms/ProjectSettings.xml'
    );
})();
