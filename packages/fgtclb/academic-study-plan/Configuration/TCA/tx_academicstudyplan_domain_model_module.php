<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_module',
        'label' => 'label',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'label,note,description',
        'iconfile' => 'EXT:academic_study_plan/Resources/Public/Icons/module.svg',
        'hideTable' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    label,
                    note,
                    credit_points,
                    description,
                    audio_file,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                    l10n_parent,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,
            ',
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
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l10n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_academicstudyplan_domain_model_module',
                'foreign_table_where' => 'AND {#tx_academicstudyplan_domain_model_module}.{#pid}=###CURRENT_PID### AND {#tx_academicstudyplan_domain_model_module}.{#sys_language_uid} IN (-1,0)',
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
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'label' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_module.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'note' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_module.note',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'credit_points' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_module.credit_points',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'number',
                'size' => 10,
                'range' => [
                    'lower' => 0,
                ],
            ],
        ],
        'description' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_module.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 10,
                'eval' => 'trim',
            ],
        ],
        'audio_file' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_module.audio_file',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'allowed' => 'mp3,wav,ogg,m4a,aac,flac,webm',
            ],
        ],
        'categories' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_module.categories',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_academicstudyplan_domain_model_category',
                'foreign_table_where' => 'AND {#tx_academicstudyplan_domain_model_category}.{#sys_language_uid} IN (0,-1) ORDER BY tx_academicstudyplan_domain_model_category.label',
                'MM' => 'tx_academicstudyplan_module_category_mm',
                'size' => 5,
                'autoSizeMax' => 10,
                'maxitems' => 99,
            ],
        ],
        'semester' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
