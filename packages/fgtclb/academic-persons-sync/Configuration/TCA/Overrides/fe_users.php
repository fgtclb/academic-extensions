<?php

declare(strict_types=1);

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
        'LLL:EXT:academic_persons_sync/Resources/Private/Language/locallang_tca.xlf:fe_users.columns.tx_extbase_type.items.Tx_Academicpersonssync_Domain_Model_FrontendUser',
        'Tx_Academicpersonssync_Domain_Model_FrontendUser',
    ]
);

$GLOBALS['TCA']['fe_users']['types']['Tx_Academicpersonssync_Domain_Model_FrontendUser'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            username,usergroup,lastlogin,
        --div--;LLL:EXT:academic_persons_edit/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.tx_academicpersons_profiles.label,
            tx_academicpersons_profiles,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.options,
            TSconfig,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            disable,--palette--;;timeRestriction,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            description,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            tx_extbase_type,
    ',
];
