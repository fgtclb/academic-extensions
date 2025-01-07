<?php

defined('TYPO3') or die;

$ll = 'LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $ll . 'tx_academiccontacts4pages_domain_model_role',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'tx_academiccontacts4pages_domain_model_role',
        ],
    ],
    'types' => [
        0 => [
            'showitem' => implode(',', [
                'name',
                'description',
                'contacts',
                '--palette--;;paletteCore',
            ]),
        ],
    ],
    'palettes' => [
        'paletteCore' => [
            'showitem' => implode(',', [
                'hidden',
                'sys_language_uid',
                'l10n_parent',
                'l10n_diffsource',
            ]),
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_academiccontacts4pages_domain_model_role',
                'foreign_table_where' => 'AND tx_academiccontacts4pages_domain_model_role.pid=###CURRENT_PID### AND tx_academiccontacts4pages_domain_model_role.sys_language_uid IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'name' => [
            'exclude' => false,
            'label' => $ll . 'tx_academiccontacts4pages_domain_model_role.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => $ll . 'tx_academiccontacts4pages_domain_model_role.description',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 5,
            ],
        ],
        'contacts' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => $ll . 'tx_academiccontacts4pages_domain_model_role.contacts',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_academiccontacts4pages_domain_model_contact',
                'foreign_field' => 'role',
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ],
            ],
        ],
    ],
];
