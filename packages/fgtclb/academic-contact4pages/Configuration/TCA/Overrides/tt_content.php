<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {

    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_be.xlf:plugin.contacts_list.title',
            'value' => 'academiccontacts4pages_list',
            'icon' => 'academic_contacts4pages',
            'group' => 'academic',
            'description' => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_be.xlf:plugin.contacts_list.description',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_contacts4pages'
    );

    // Add a configuration tab and the FlexForm field to the plugin
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
        ]),
        'academiccontacts4pages_list',
        'after:header'
    );

    // Link the FlexForm configuration to the pi_flexform field
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_contacts4pages/Configuration/FlexForms/ContactsList.xml',
        'academiccontacts4pages_list',
    );
})();
