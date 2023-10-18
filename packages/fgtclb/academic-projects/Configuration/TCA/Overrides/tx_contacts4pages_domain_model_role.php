<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die(__CLASS__);
}

(static function (): void {
    $ll = function (string $langKey): string {
        return 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_db.xlf:' . $langKey;
    };

    $doktype = \FGTCLB\AcademicProjects\Domain\Enumeration\Page::TYPE_ACEDEMIC_PROJECT;

    // Add new doktype item
    ExtensionManagementUtility::addTcaSelectItem(
        'tx_contacts4pages_domain_model_role',
        'doktypes',
        [
            $ll('pages.doktype.item.academic_projects'),
            $doktype,
            'actions-code-merge',
        ]
    );
})();
