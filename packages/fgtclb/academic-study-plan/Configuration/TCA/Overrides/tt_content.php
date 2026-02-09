<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $temporaryColumns = [
        'tx_academicstudyplan_footer_note' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tt_content.footer_note',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'cols' => 40,
                'rows' => 10,
            ],
        ],
        'tx_academicstudyplan_semesters' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tt_content.semesters',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_academicstudyplan_domain_model_semester',
                'foreign_field' => 'content_element',
                'foreign_sortby' => 'sorting',
                'maxitems' => 99,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'modules' => [
                            'config' => [
                                'behaviour' => [
                                    'allowLanguageSynchronization' => true,
                                ],
                            ],
                        ],
                    ],
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
    ];
    ExtensionManagementUtility::addTCAcolumns('tt_content', $temporaryColumns);

    // Use `TcaManipulator->addRecordType()` TYPO3 backport method to register custom tt_content.CType.
    // @todo typo3/cms-core >= 13.4 Use `ExtensionManagementUtility::addRecordType()` when v12 support is dropped.
    $tcaManipulator = new \FGTCLB\AcademicBase\TcaManipulator();
    $tcaManipulator->addRecordType(
        [
            'label' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tt_content.CType.academic_study_plan.title',
            'description' => 'LLL:EXT:academic_study_plan/Resources/Private/Language/locallang_be.xlf:tt_content.CType.academic_study_plan.description',
            'value' => 'academic_study_plan',
            'icon' => 'academic-study-plan',
            'group' => 'academic',
        ],
        '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                --palette--;;headers,
                tx_academicstudyplan_semesters,
                tx_academicstudyplan_footer_note,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                rowDescription,
        ',
    );

})();
