<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\TcaManipulator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die;

(static function (): void {
    // ------------------------------------------------------------------------
    // Add custom content element group for acadmic plugins
    // ------------------------------------------------------------------------

    // ------------------------------------------------------------------------
    // Add the academicprojects_projectlist plugin
    // ------------------------------------------------------------------------

    // Add plugin to the CType selection
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:plugin.project_list.name',
            'value' => 'academicprojects_projectlist',
            'icon' => 'actions-code-merge',
            'group' => 'academic',
        ],
        'academic_projects'
    );

    // Add a configuration tab and the FlexForm configuration to plugin
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
            'pages;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:element.field.pages',
        ]),
        'academicprojects_projectlist',
        'after:header'
    );

    // Link the FlexForm configuration to the pi_flexform field
    $GLOBALS['TCA']['tt_content']['types']['academicprojects_projectlist']['columnsOverrides']['pi_flexform']['config']['ds']
        = 'FILE:EXT:academic_projects/Configuration/FlexForms/ProjectSettings.xml';

    // ------------------------------------------------------------------------
    // Add the academicprojects_projectlistsingle plugin
    // ------------------------------------------------------------------------

    // Add plugin to the CType selection
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:plugin.project_selected.name',
            'value' => 'academicprojects_projectlistsingle',
            'icon' => 'actions-code-merge',
            'group' => 'academic',
        ],
        'academic_projects'
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
            'pages',
        ]),
        'academicprojects_projectlistsingle',
        'after:header'
    );

    // Link the FlexForm configuration to the pi_flexform field
    // @todo Add FlexForm options to select a list of projects
    $GLOBALS['TCA']['tt_content']['types']['academicprojects_projectlistsingle']['columnsOverrides']['pi_flexform']['config']['ds']
        = 'FILE:EXT:academic_projects/Configuration/FlexForms/ProjectSettings.xml';

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
            'pages;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:element.field.pages.selected',
        ]),
        'academicprojects_projectlistsingle',
        'after:header'
    );
})();
