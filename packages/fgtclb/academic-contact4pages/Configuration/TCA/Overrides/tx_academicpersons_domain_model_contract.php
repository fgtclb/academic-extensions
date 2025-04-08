<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
    ExtensionManagementUtility::addTCAcolumns(
        'tx_academicpersons_domain_model_contract',
        [
            'tx_academiccontacts4pages_contacts' => [
                'exclude' => true,
                'label' => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:tx_academicpersons_domain_model_contract.tx_academiccontacts4pages_contacts',
                'config' => [
                    'type' => 'inline',
                    'appearance' => [
                        'collapseAll' => true,
                        'expandSingle' => false,
                        'showNewRecordLink' => true,
                        'newRecordLinkAddTitle' => false,
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
                        'newRecordLinkTitle' => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:tx_academicpersons_domain_model_contract.tx_academiccontacts4pages_domain_model_contact.button',
                    ],
                    'behavior' => [
                        'enableCascadingDelete' => true,
                    ],
                    'foreign_field' =>  'contract',
                    'foreign_sortby' => 'sorting',
                    'foreign_table' => 'tx_academiccontacts4pages_domain_model_contact',
                    'foreign_label' => 'page',
                ],
            ],
        ]
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tx_academicpersons_domain_model_contract',
        implode(',', [
            '--div--;LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:tx_academicpersons_domain_model_contract.tx_academiccontacts4pages_contacts',
            'tx_academiccontacts4pages_contacts',
        ]),
        '',
        'after:phone_numbers'
    );
})();
