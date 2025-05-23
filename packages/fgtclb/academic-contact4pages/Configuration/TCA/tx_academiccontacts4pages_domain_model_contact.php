<?php

use FGTCLB\AcademicContacts4pages\Backend\FormEngine\ContactLabels;
use FGTCLB\AcademicContacts4pages\Backend\FormEngine\ContractItemsProcFunc;

if (!defined('TYPO3')) {
    die('Not authorized');
}

return [
    'ctrl' => [
        'title' => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:tx_academiccontacts4pages_domain_model_contact',
        'label' => 'uid',
        'label_userFunc' => ContactLabels::class . '->getTitle',
        'hideTable' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
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
            'label' => 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:tx_academiccontacts4pages_domain_model_contact.page',
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
                    [
                        // @todo empty labels does not make sense, they are not really selectable. Consider to a defaultlike `-n/a-` or `- please choose -`
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'itemsProcFunc' => ContractItemsProcFunc::class . '->itemsProcFunc',
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
                    [
                        // @todo empty labels does not make sense, they are not really selectable. Consider to a defaultlike `-n/a-` or `- please choose -`
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_academiccontacts4pages_domain_model_role',
                'foreign_table_where' => 'AND {#tx_academiccontacts4pages_domain_model_role}.{#sys_language_uid} = 0 ORDER BY tx_academiccontacts4pages_domain_model_role.name ASC',
                'minitems' => 1,
                'default' => 0,
            ],
        ],
    ],
];
