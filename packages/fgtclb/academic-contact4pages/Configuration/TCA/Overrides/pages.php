<?php

use FGTCLB\AcademicBase\TcaManipulator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {

    //==================================================================================================================
    // Page TSconfig, selectable in the page field "Page TSconfig" for installations that do not use site sets.
    //
    // The files are the same ones the sets of this extension deliver. Use one mechanism per site, not both.
    //==================================================================================================================
    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_contacts4pages',
        'Configuration/TSconfig/List/page.tsconfig',
        'Academic Contacts4Pages: Contact list',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_contacts4pages',
        'Configuration/TSconfig/Full/page.tsconfig',
        'Academic Contacts4Pages: All components',
    );

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
                    'behavior' => [
                        'enableCascadingDelete' => true,
                    ],
                    'foreign_field' =>  'page',
                    'foreign_sortby' => 'sorting',
                    'foreign_table' => 'tx_academiccontacts4pages_domain_model_contact',
                ],
            ],
        ]
    );

    $GLOBALS['TCA'] = GeneralUtility::makeInstance(TcaManipulator::class)->addToPageTypesGeneralTab(
        $GLOBALS['TCA'],
        implode(',', [
            '--div--;LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:pages.tx_academiccontacts4pages_contacts',
            'tx_academiccontacts4pages_contacts',
        ]),
        [],
        [254, 255]
    );
})();
