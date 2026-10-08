<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/*
 * This file is part of the "academic_persons_sync" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

ExtensionManagementUtility::addTcaSelectItem(
    'fe_users',
    'tx_extbase_type',
    [
        'label' => 'LLL:EXT:academic_persons_sync/Resources/Private/Language/locallang_tca.xlf:fe_users.columns.tx_extbase_type.items.Tx_Academicpersonssync_Domain_Model_FrontendUser',
        'value' => 'Tx_Academicpersonssync_Domain_Model_FrontendUser',
    ],
);

(static function (): void {
    // TYPO3 v13 removed the field fe_users.TSconfig together with the tab that
    // held it, and with it the label of that tab.
    $optionsTab = (new Typo3Version())->getMajorVersion() < 13
        ? '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.options, TSconfig,'
        : '';
    $GLOBALS['TCA']['fe_users']['types']['Tx_Academicpersonssync_Domain_Model_FrontendUser'] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                username,usergroup,lastlogin,
            --div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.tx_academicpersons_profiles.label,
                tx_academicpersons_profiles,
            ' . $optionsTab . '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                disable,--palette--;;timeRestriction,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                tx_extbase_type,
        ',
    ];
})();
