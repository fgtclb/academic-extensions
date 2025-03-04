<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    ExtensionUtility::registerPlugin(
        'AcademicContacts4pages',
        'ContactsList',
        'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_be.xlf:plugin.contacts_list.title',
        'EXT:academic_contacts4pages/Resources/Public/Icons/Extension.svg',
        'academic',
        'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_be.xlf:plugin.contacts_list.description',
    );
})();
