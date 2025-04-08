<?php

defined('TYPO3') or die;

$ll = 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $ll . 'tx_academiccontacts4pages_domain_model_contact',
        'label' => 'uid',
        'label_userFunc' => \FGTCLB\AcademicContacts4pages\Backend\FormEngine\ContactLabels::class . '->getTitle',
        'hideTable' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'typeicon_classes' => [
            'default' => 'tx_academiccontacts4pages_domain_model_contact',
        ],
        // The usage of method allowTableOnStandardPages() is deprecated with v12.0 and replaced with TCA setting.
        // @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/FileStructure/ExtTables.html#extension-configuration-files-allow-table-standard
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        0 => [
            'showitem' => implode(',', [
                'page',
                'contract',
                'role',
                'hidden',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language',
                'sys_language_uid',
                'l10n_parent',
            ]),
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'page' => [
            'exclude' => true,
            'label' => $ll . 'tx_academiccontacts4pages_domain_model_contact.page',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'maxitems' => 1,
                'minitems' => 1,
                'size' => 1,
                'default' => 0,
            ],
        ],
        'contract' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:tx_academiccontacts4pages_domain_model_contact.contract',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'itemsProcFunc' => \FGTCLB\AcademicContacts4pages\Backend\FormEngine\ContractItemsProcFunc::class . '->itemsProcFunc',
                'minitems' => 1,
                'default' => 0,
            ],
        ],
        'role' => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:tx_academiccontacts4pages_domain_model_contact.role',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_academiccontacts4pages_domain_model_role',
                'foreign_table_where' => 'AND {#tx_academiccontacts4pages_domain_model_role}.{#sys_language_uid} = 0 ORDER BY tx_academiccontacts4pages_domain_model_role.name ASC',
                'minitems' => 1,
                'default' => 0,
            ],
        ],
    ],
];
