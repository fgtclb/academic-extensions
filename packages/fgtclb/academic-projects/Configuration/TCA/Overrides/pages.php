<?php

use FGTCLB\AcademicBase\TcaManipulator;
use FGTCLB\AcademicProjects\Enumeration\PageTypes;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
    // Create new item group for academic doktype items
    // TODO: Harmonize this with all academic extensions
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'pages',
        'doktype',
        'academic',
        'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.doktype.item_group.academic',
        'after:default'
    );

    // Add and configure new doktype
    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'doktype',
        [
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.doktype.item.academic_project',
            'value' => PageTypes::TYPE_ACEDEMIC_PROJECT,
            'icon' => 'actions-code-merge',
            'group' => 'academic',
        ],
        '254',
        'before'
    );

    ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['pages'],
        [
            'ctrl' => [
                'typeicon_classes' => [
                    PageTypes::TYPE_ACEDEMIC_PROJECT => 'actions-code-merge',
                ],
            ],
            'types' => [
                PageTypes::TYPE_ACEDEMIC_PROJECT => $GLOBALS['TCA']['pages']['types'][(string)PageRepository::DOKTYPE_DEFAULT],
            ],
        ]
    );

    // TYPO3 v14+ resolves the tables allowed on a page type from this TCA option. TYPO3
    // v13 has no such option and needs the PageDoktypeRegistry, which is fed by the
    // RegisterAcademicPageDoktype event listener - see its docblock for why it cannot
    // happen here.
    $GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACEDEMIC_PROJECT]['allowedRecordTypes'] = ['*'];

    $columns = [
        'tx_academicprojects_project_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.project_title',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'tx_academicprojects_short_description' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.short_description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'tx_academicprojects_start_date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.start_date',
            'config' => [
                'type' => 'datetime',
                'eval' => implode(',', [
                    'date',
                    'int',
                ]),
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'tx_academicprojects_end_date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.end_date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'tx_academicprojects_budget' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.budget',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'nullable' => true,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'tx_academicprojects_funders' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.funders',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
    ];

    ExtensionManagementUtility::addTCAcolumns('pages', $columns);
    ExtensionManagementUtility::addFieldsToPalette(
        'pages',
        'project_info',
        implode(',', [
            'tx_academicprojects_project_title',
            '--linebreak--',
            'tx_academicprojects_short_description',
            '--linebreak--',
            'tx_academicprojects_budget',
            '--linebreak--',
            'tx_academicprojects_funders',
        ])
    );

    ExtensionManagementUtility::addFieldsToPalette(
        'pages',
        'project_date',
        implode(',', [
            'tx_academicprojects_start_date',
            'tx_academicprojects_end_date',
        ])
    );

    if (!isset($GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACEDEMIC_PROJECT]['columnsOverrides'])) {
        $GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACEDEMIC_PROJECT]['columnsOverrides'] = [];
    }

    $GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACEDEMIC_PROJECT]['columnsOverrides']['title']['config']['max'] = 60;
    //$GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACEDEMIC_PROJECT]['columnsOverrides']['categories']['l10n_mode'] = 'exclude';

    // The media of a project page offers named crop variants, which templates request by name.
    // They are set on the page type, so the media of a standard page keeps what TYPO3 offers.
    // FormEngine merges "columnsOverrides" into the field, so the "overrideChildTca" TYPO3 v13
    // still configures on it stays. "default" repeats what the cropper offers when no variant
    // is configured, and stays first, so a crop stored before the variants existed keeps its
    // meaning.
    $GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACEDEMIC_PROJECT]['columnsOverrides']['media']['config']['overrideChildTca']['columns']['crop']['config']['cropVariants'] = [
        'default' => [
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
            'allowedAspectRatios' => [
                '16:9' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.16_9',
                    'value' => 16 / 9,
                ],
                '3:2' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.3_2',
                    'value' => 3 / 2,
                ],
                '4:3' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.4_3',
                    'value' => 4 / 3,
                ],
                '1:1' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.1_1',
                    'value' => 1.0,
                ],
                'NaN' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
                    'value' => 0.0,
                ],
            ],
            'selectedRatio' => 'NaN',
            'cropArea' => [
                'x' => 0.0,
                'y' => 0.0,
                'width' => 1.0,
                'height' => 1.0,
            ],
        ],
        'landscape' => [
            'title' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.media.crop_variant.landscape',
            'allowedAspectRatios' => [
                '16:9' => [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.16_9',
                    'value' => 16 / 9,
                ],
            ],
            'selectedRatio' => '16:9',
        ],
        'portrait' => [
            'title' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.media.crop_variant.portrait',
            'allowedAspectRatios' => [
                '3:4' => [
                    'title' => 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.media.aspect_ratio.3_4',
                    'value' => 3 / 4,
                ],
            ],
            'selectedRatio' => '3:4',
        ],
    ];

    $GLOBALS['TCA'] = GeneralUtility::makeInstance(TcaManipulator::class)->addToPageTypesGeneralTab(
        $GLOBALS['TCA'],
        implode(',', [
            '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project',
            '--palette--;;project_info',
            '--palette--;;project_date',
        ]),
        [PageTypes::TYPE_ACEDEMIC_PROJECT]
    );

    //==================================================================================================================
    // Page TSconfig, selectable in the page field "Page TSconfig" for installations that do not use site sets.
    //
    // The files are the same ones the sets of this extension deliver. Use one mechanism per site, not both.
    //
    // The page type registered above and its backend layout are deliberately NOT part of this: they are stored on page
    // records, so they have to resolve on every installation. The page type is registered in TCA and the backend layout
    // is imported by the always included "Configuration/page.tsconfig".
    //==================================================================================================================
    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_projects',
        'Configuration/TSconfig/ProjectList/page.tsconfig',
        'Academic Projects: Projects',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_projects',
        'Configuration/TSconfig/ProjectListSingle/page.tsconfig',
        'Academic Projects: Projects (selected)',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_projects',
        'Configuration/TSconfig/Full/page.tsconfig',
        'Academic Projects: All components',
    );
})();
