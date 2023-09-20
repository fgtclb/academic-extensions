<?php

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

if (!defined('TYPO3')) {
    die(__CLASS__);
}

(static function (): void {
    $ll = function (string $langKey): string {
        return 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_db.xlf:' . $langKey;
    };

    $doktype = \FGTCLB\AcademicProjects\Domain\Enumeration\Page::TYPE_EDUCATIONAL_PROJECT;

    // Add new page type as possible select item:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'doktype',
        [
            $ll('pages.doktype.projects'),
            $doktype,
            'actions-code-merge',
        ],
        '254',
        'before'
    );

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['pages'],
        [
            'ctrl' => [
                'typeicon_classes' => [
                    $doktype => 'actions-code-merge',
                ],
            ],
            'types' => [
                $doktype => $GLOBALS['TCA']['pages']['types'][(string)PageRepository::DOKTYPE_DEFAULT],
            ],
        ]
    );

    $columns = [
        'tx_academicprojects_project_title' => [
            'exclude' => true,
            'label' => $ll('tx_academicprojects_domain_model_project.project_title'),
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
            'label' => $ll('tx_academicprojects_domain_model_project.short_description'),
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
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'tx_academicprojects_start_date' => [
            'exclude' => true,
            'label' => $ll('tx_academicprojects_domain_model_project.start_date'),
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date',
                'size' => 7,
            ],
        ],
        'tx_academicprojects_end_date' => [
            'exclude' => true,
            'label' => $ll('tx_academicprojects_domain_model_project.end_date'),
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date',
                'size' => 7,
            ],
        ],
        'tx_academicprojects_budget' => [
            'exclude' => true,
            'label' => $ll('tx_academicprojects_domain_model_project.budget'),
            'config' => [
                'type' => 'input',
                'eval' => 'double2',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'tx_academicprojects_funders' => [
            'exclude' => true,
            'label' => $ll('tx_academicprojects_domain_model_project.funders'),
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

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $columns);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'project_info', implode(',', [
        'tx_academicprojects_project_title',
        '--linebreak--',
        'tx_academicprojects_short_description',
        '--linebreak--',
        'tx_academicprojects_budget',
        '--linebreak--',
        'tx_academicprojects_funders',
    ]));

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'project_date', implode(',', [
        'tx_academicprojects_start_date',
        'tx_academicprojects_end_date',
    ]));

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', implode(',', [
        '--div--;Project',
        '--palette--;;project_info',
        '--palette--;;project_date',
    ]), (string)$doktype, 'after:title');
})();
