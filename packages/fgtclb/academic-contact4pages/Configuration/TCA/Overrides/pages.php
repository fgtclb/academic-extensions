<?php

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
    $ll = fn(string $langKey): string => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:' . $langKey;

    $columns = [
        'tx_academiccontacts4pages_contacts' => [
            'exclude' => true,
            'label' => $ll('pages.tx_academiccontacts4pages_contacts'),
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
                    'fileUploadAllowed' => false,
                    'fileByUrlAllowed' => false,
                    'elementBrowserEnabled' => false,
                ],
                'enableCascadingDelete' => true,
                'foreign_field' =>  'page',
                'foreign_sortby' => 'sorting',
                'foreign_table' => 'tx_academiccontacts4pages_domain_model_contact',
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $columns);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        implode(',', [
            '--div--;' . $ll('pages.tx_academiccontacts4pages_contacts'),
            'tx_academiccontacts4pages_contacts',
        ]),
        '',
        'after:title'
    );
})();
