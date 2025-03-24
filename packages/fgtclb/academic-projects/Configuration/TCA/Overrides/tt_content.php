<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $typo3MajorVersion = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();

    // ------------------------------------------------------------------------
    // Add custom content element group for acadmic plugins
    // ------------------------------------------------------------------------

    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academic',
        'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label',
    );

    // ------------------------------------------------------------------------
    // Add the academicprojects_projectlist plugin
    // ------------------------------------------------------------------------

    // Add plugin to the CType selection
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_db.xlf:tx_academic_projects_p1.name',
            'value' => 'academicprojects_projectlist',
            'icon' => 'actions-code-merge',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academicprojects_projectlist'
    );

    // Add a configuration tab and the FlexForm configuration to plugin
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
        ]),
        'academicprojects_projectlist',
        'after:header'
    );

    // Exclude/add fields for the plugin
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicprojects_projectlist'] = 'layout,recursive';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicprojects_projectlist'] = 'pi_flexform';

    // Link the FlexForm configuration to the pi_flexform field
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_projects/Configuration/FlexForms/ProjectSettings.xml',
        'academicprojects_projectlist',
    );

    // ------------------------------------------------------------------------
    // Add the academicprojects_projectlistsingle plugin
    // ------------------------------------------------------------------------

    // Add plugin to the CType selection
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_db.xlf:tx_academic_projects_p2.name',
            'value' => 'academicprojects_projectlistsingle',
            'icon' => 'actions-code-merge',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academicprojects_projectlistsingle'
    );

    // Exclude/add fields for the plugin
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicprojects_projectlistsingle'] = 'layout,recursive';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicprojects_projectlistsingle'] = 'pi_flexform';

    // Link the FlexForm configuration to the pi_flexform field
    // @todo Add FlexForm options to select a list of projects
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_projects/Configuration/FlexForms/ProjectSettings.xml',
        'academicprojects_projectlistsingle',
    );
})();
