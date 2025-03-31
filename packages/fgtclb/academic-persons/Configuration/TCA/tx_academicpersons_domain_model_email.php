<?php

declare(strict_types=1);

use Fgtclb\AcademicPersons\Tca\RecordTypes;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
$typo3MajorVersion = (new Typo3Version())->getMajorVersion();
$selectLabelKey = ($typo3MajorVersion >= 12) ? 'label' : 0;
$selectValueKey = ($typo3MajorVersion >= 12) ? 'value' : 1;
return [
    'ctrl' => [
        'label' => 'email',
        'default_sortby' => 'sorting',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_email.ctrl.label',
        'delete' => 'deleted',
        'hideTable' => true,
        'origUid' => 't3_origuid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'email',
        'typeicon_classes' => [
            'default' => 'tx_academicpersons_domain_model_email',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        $selectLabelKey => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
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
                    [
                        $selectLabelKey => '',
                        $selectValueKey => 0,
                    ],
                ],
                'foreign_table' => 'tx_academicpersons_domain_model_email',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_email}.{#pid}=###CURRENT_PID### AND {#tx_academicpersons_domain_model_email}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'contract' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_academicpersons_domain_model_contract',
                'maxitems' => 1,
            ],
        ],
        'email' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_email.columns.email.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
            ],
        ],
        'type' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_email.columns.type.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        $selectLabelKey => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_email.columns.type.items.undefined.label',
                        $selectValueKey => '',
                    ],
                ],
                'itemsProcFunc' => RecordTypes::class . '->getEmailAddressTypes',
            ],
        ],
    ],
    'palettes' => [
        'general' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general',
            'showitem' => implode(',', [
                'email',
                'type',
            ]),
        ],
        'language' => [
            'showitem' => implode(',', [
                'sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel',
                'l10n_parent',
            ]),
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                '--palette--;;general',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language',
                '--palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
    ],
];
