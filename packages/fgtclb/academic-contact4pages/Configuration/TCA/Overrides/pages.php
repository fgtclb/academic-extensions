<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
    ExtensionManagementUtility::addTCAcolumns(
        'pages',
        [
            'tx_academiccontacts4pages_contacts' => [
                'exclude' => true,
                'label' => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:pages.tx_academiccontacts4pages_contacts',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'inline',
                    'appearance' => [
                        'collapseAll' => true,
                        'expandSingle' => false,
                        'showNewRecordLink' => true,
                        'newRecordLinkAddTitle' => true,
                        'levelLinksPosition' => 'top',
                        'useCombination' => false,
                        'suppressCombinationWarning' => false,
                        'useSortable' => true,
                        'showPossibleLocalizationRecords' => false,
                        'showAllLocalizationLink' => false,
                        'showSynchronizationLink' => false,
                        'enabledControls' => [
                            'info' => true,
                            'new' =>  true,
                            'dragdrop' => true,
                            'sort' => false,
                            'hide' => true,
                            'delete' => true,
                            'localize' => true,
                        ],
                        'showPossibleRecordsSelector' => false,
                        'elementBrowserEnabled' => false,
                    ],
                    'enableCascadingDelete' => true,
                    'foreign_field' =>  'page',
                    'foreign_sortby' => 'sorting',
                    'foreign_table' => 'tx_academiccontacts4pages_domain_model_contact',
                ],
            ],
        ]
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        implode(',', [
            '--div--;LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:pages.tx_academiccontacts4pages_contacts',
            'tx_academiccontacts4pages_contacts',
        ]),
        '',
        'after:title'
    );
})();
