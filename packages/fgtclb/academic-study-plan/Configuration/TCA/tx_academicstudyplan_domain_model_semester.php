<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_semester',
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
        'searchFields' => 'label,note',
        'iconfile' => 'EXT:academic_study_plan/Resources/Public/Icons/semester.svg',
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
                    modules,
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
                'foreign_table' => 'tx_academicstudyplan_domain_model_semester',
                'foreign_table_where' => 'AND {#tx_academicstudyplan_domain_model_semester}.{#pid}=###CURRENT_PID### AND {#tx_academicstudyplan_domain_model_semester}.{#sys_language_uid} IN (-1,0)',
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
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_semester.label',
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
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_semester.note',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'credit_points' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_semester.credit_points',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'number',
                'size' => 10,
                'range' => [
                    'lower' => 0,
                ],
            ],
        ],
        'modules' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tx_academicstudyplan_domain_model_semester.modules',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_academicstudyplan_domain_model_module',
                'foreign_field' => 'semester',
                'foreign_sortby' => 'sorting',
                'maxitems' => 99,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'levelLinksPosition' => 'bottom',
                    'showSynchronizationLink' => true,
                    'showPossibleLocalizationRecords' => false,
                    'showAllLocalizationLink' => false,
                    'useSortable' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' => true,
                        'dragdrop' => true,
                        'sort' => true,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                ],
            ],
        ],
        'content_element' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
