<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {

    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academic',
        'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label',
    );

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
})();
