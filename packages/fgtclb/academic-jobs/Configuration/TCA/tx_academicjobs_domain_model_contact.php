<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang.xlf:tx_academicjobs_domain_model_contact',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'name,email,phone,additional_information',
        'iconfile' => 'EXT:academic_jobs/Resources/Public/Icons/tx_academicjobs_domain_model_contact.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'name, email, phone, additional_information, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime'],
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
                'default' => 0,
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_academicjobs_domain_model_contact',
                'foreign_table_where' => 'AND {#tx_academicjobs_domain_model_contact}.{#pid}=###CURRENT_PID### AND {#tx_academicjobs_domain_model_contact}.{#sys_language_uid} IN (-1,0)',
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
                        'value' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang.xlf:tx_academicjobs_domain_model_contact.name',
            'description' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang.xlf:tx_academicjobs_domain_model_contact.name.description',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'email' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang.xlf:tx_academicjobs_domain_model_contact.email',
            'description' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang.xlf:tx_academicjobs_domain_model_contact.email.description',
            'config' => [
                'type' => 'email',
                'size' => 30,
                'eval' => 'nospace',
                'default' => '',
            ],
        ],
        'phone' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang.xlf:tx_academicjobs_domain_model_contact.phone',
            'description' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang.xlf:tx_academicjobs_domain_model_contact.phone.description',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'additional_information' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang.xlf:tx_academicjobs_domain_model_contact.additional_information',
            'description' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang.xlf:tx_academicjobs_domain_model_contact.additional_information.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
    ],
];
