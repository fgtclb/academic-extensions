<?php

declare(strict_types=1);

(static function (): void {
    $ll = function (string $langKey): string {
        return 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_db.xlf:' . $langKey;
    };

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
})();
