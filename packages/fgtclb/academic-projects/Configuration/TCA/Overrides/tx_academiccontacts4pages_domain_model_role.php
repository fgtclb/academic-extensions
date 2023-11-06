<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die(__CLASS__);
}

(static function (): void {
    // Field configuration only use with extension "academic_contacts4pages" and table
    if (!ExtensionManagementUtility::isLoaded('academic_contacts4pages')) {
        return;
    }
    if (!isset($GLOBALS['TCA']['tx_academiccontacts4pages_domain_model_role'])) {
        return;
    }

    $ll = function (string $langKey): string {
        return 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_db.xlf:' . $langKey;
    };

    ExtensionManagementUtility::addTcaSelectItem(
        'tx_academiccontacts4pages_domain_model_role',
        'doktypes',
        [
            $ll('pages.doktype.item.academic_projects'),
            \FGTCLB\AcademicProjects\Domain\Enumeration\Page::TYPE_ACEDEMIC_PROJECT,
            'actions-code-merge',
        ]
    );
})();
