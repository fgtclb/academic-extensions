<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit;

use FGTCLB\AcademicBase\TcaManipulator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaManipulatorTest extends UnitTestCase
{
    public static function returnsExpectedTcaArrayDataSet(): \Generator
    {
        yield 'custom tab is added after the general tab not moving palettes to wrong tab for all types' => [
            'tca' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                                    --palette--;;standard,
                                    --palette--;;title,
                                    --palette--;;customPalette,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                                    --palette--;;abstract,
                                    --palette--;;metatags,
                                    --palette--;;editorial,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
                                    --palette--;;layout,
                                    --palette--;;replace,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
                                    --palette--;;links,
                                    --palette--;;caching,
                                    --palette--;;miscellaneous,
                                    --palette--;;module,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                                    --palette--;;media,
                                    --palette--;;config,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                                    --palette--;;language,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                                    --palette--;;visibility,
                                    --palette--;;access,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                    categories,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                    rowDescription,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                            ',
                        ],
                        2 => [
                            'showitem' => '
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                                    --palette--;;standard,
                                    --palette--;;title,
                                    --palette--;;customPalette,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                                    --palette--;;abstract,
                                    --palette--;;metatags,
                                    --palette--;;editorial,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
                                    --palette--;;layout,
                                    --palette--;;replace,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
                                    --palette--;;links,
                                    --palette--;;caching,
                                    --palette--;;miscellaneous,
                                    --palette--;;module,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                                    --palette--;;media,
                                    --palette--;;config,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                                    --palette--;;language,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                                    --palette--;;visibility,
                                    --palette--;;access,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                    categories,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                    rowDescription,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                            ',
                        ],
                    ],
                ],
            ],
            'definitionToAdd' => '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,',
            'types' => [],
            'excludeTypes' => [],
            'expected' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
--palette--;;standard,
--palette--;;title,
--palette--;;customPalette,
--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
--palette--;;abstract,
--palette--;;metatags,
--palette--;;editorial,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
--palette--;;layout,
--palette--;;replace,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
--palette--;;links,
--palette--;;caching,
--palette--;;miscellaneous,
--palette--;;module,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
--palette--;;media,
--palette--;;config,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
--palette--;;language,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
--palette--;;visibility,
--palette--;;access,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,categories,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,rowDescription,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
                        ],
                        2 => [
                            'showitem' => '
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
--palette--;;standard,
--palette--;;title,
--palette--;;customPalette,
--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
--palette--;;abstract,
--palette--;;metatags,
--palette--;;editorial,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
--palette--;;layout,
--palette--;;replace,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
--palette--;;links,
--palette--;;caching,
--palette--;;miscellaneous,
--palette--;;module,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
--palette--;;media,
--palette--;;config,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
--palette--;;language,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
--palette--;;visibility,
--palette--;;access,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,categories,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,rowDescription,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
                        ],
                    ],
                ],
            ],
        ];

        yield 'custom tab is added after the general tab not moving palettes to wrong tab for selected type only' => [
            'tca' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                                    --palette--;;standard,
                                    --palette--;;title,
                                    --palette--;;customPalette,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                                    --palette--;;abstract,
                                    --palette--;;metatags,
                                    --palette--;;editorial,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
                                    --palette--;;layout,
                                    --palette--;;replace,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
                                    --palette--;;links,
                                    --palette--;;caching,
                                    --palette--;;miscellaneous,
                                    --palette--;;module,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                                    --palette--;;media,
                                    --palette--;;config,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                                    --palette--;;language,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                                    --palette--;;visibility,
                                    --palette--;;access,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                    categories,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                    rowDescription,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                            ',
                        ],
                        2 => [
                            'showitem' => '
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                                    --palette--;;standard,
                                    --palette--;;title,
                                    --palette--;;customPalette,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                                    --palette--;;abstract,
                                    --palette--;;metatags,
                                    --palette--;;editorial,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
                                    --palette--;;layout,
                                    --palette--;;replace,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
                                    --palette--;;links,
                                    --palette--;;caching,
                                    --palette--;;miscellaneous,
                                    --palette--;;module,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                                    --palette--;;media,
                                    --palette--;;config,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                                    --palette--;;language,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                                    --palette--;;visibility,
                                    --palette--;;access,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                    categories,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                    rowDescription,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                            ',
                        ],
                    ],
                ],
            ],
            'definitionToAdd' => '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,',
            'types' => [1],
            'excludeTypes' => [],
            'expected' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
--palette--;;standard,
--palette--;;title,
--palette--;;customPalette,
--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
--palette--;;abstract,
--palette--;;metatags,
--palette--;;editorial,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
--palette--;;layout,
--palette--;;replace,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
--palette--;;links,
--palette--;;caching,
--palette--;;miscellaneous,
--palette--;;module,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
--palette--;;media,
--palette--;;config,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
--palette--;;language,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
--palette--;;visibility,
--palette--;;access,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,categories,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,rowDescription,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
                        ],
                        2 => [
                            'showitem' => '
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                                    --palette--;;standard,
                                    --palette--;;title,
                                    --palette--;;customPalette,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                                    --palette--;;abstract,
                                    --palette--;;metatags,
                                    --palette--;;editorial,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
                                    --palette--;;layout,
                                    --palette--;;replace,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
                                    --palette--;;links,
                                    --palette--;;caching,
                                    --palette--;;miscellaneous,
                                    --palette--;;module,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                                    --palette--;;media,
                                    --palette--;;config,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                                    --palette--;;language,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                                    --palette--;;visibility,
                                    --palette--;;access,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                    categories,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                    rowDescription,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                            ',
                        ],
                    ],
                ],
            ],
        ];

        yield 'custom tab is added after the general tab not moving palettes to wrong tab for not excluded types' => [
            'tca' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                                    --palette--;;standard,
                                    --palette--;;title,
                                    --palette--;;customPalette,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                                    --palette--;;abstract,
                                    --palette--;;metatags,
                                    --palette--;;editorial,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
                                    --palette--;;layout,
                                    --palette--;;replace,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
                                    --palette--;;links,
                                    --palette--;;caching,
                                    --palette--;;miscellaneous,
                                    --palette--;;module,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                                    --palette--;;media,
                                    --palette--;;config,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                                    --palette--;;language,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                                    --palette--;;visibility,
                                    --palette--;;access,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                    categories,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                    rowDescription,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                            ',
                        ],
                        2 => [
                            'showitem' => '
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                                    --palette--;;standard,
                                    --palette--;;title,
                                    --palette--;;customPalette,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                                    --palette--;;abstract,
                                    --palette--;;metatags,
                                    --palette--;;editorial,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
                                    --palette--;;layout,
                                    --palette--;;replace,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
                                    --palette--;;links,
                                    --palette--;;caching,
                                    --palette--;;miscellaneous,
                                    --palette--;;module,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                                    --palette--;;media,
                                    --palette--;;config,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                                    --palette--;;language,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                                    --palette--;;visibility,
                                    --palette--;;access,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                    categories,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                    rowDescription,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                            ',
                        ],
                    ],
                ],
            ],
            'definitionToAdd' => '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,',
            'types' => [],
            'excludeTypes' => [2],
            'expected' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
--palette--;;standard,
--palette--;;title,
--palette--;;customPalette,
--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
--palette--;;abstract,
--palette--;;metatags,
--palette--;;editorial,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
--palette--;;layout,
--palette--;;replace,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
--palette--;;links,
--palette--;;caching,
--palette--;;miscellaneous,
--palette--;;module,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
--palette--;;media,
--palette--;;config,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
--palette--;;language,
--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
--palette--;;visibility,
--palette--;;access,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,categories,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,rowDescription,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
                        ],
                        2 => [
                            'showitem' => '
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                                    --palette--;;standard,
                                    --palette--;;title,
                                    --palette--;;customPalette,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                                    --palette--;;abstract,
                                    --palette--;;metatags,
                                    --palette--;;editorial,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,
                                    --palette--;;layout,
                                    --palette--;;replace,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,
                                    --palette--;;links,
                                    --palette--;;caching,
                                    --palette--;;miscellaneous,
                                    --palette--;;module,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                                    --palette--;;media,
                                    --palette--;;config,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                                    --palette--;;language,
                                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                                    --palette--;;visibility,
                                    --palette--;;access,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                    categories,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                    rowDescription,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                            ',
                        ],
                    ],
                ],
            ],
        ];

        // The three data sets above use the TYPO3 v13 spelling of the core tab
        // labels and place the extended tab last, where extracting it and
        // appending it again cannot be told apart from doing nothing.
        //
        // The two below put a further tab *after* the extended tab, so the
        // extraction is observable, and cover both spellings: TYPO3 v14 moved
        // the core `showitem` definitions to short form label references
        // (breaking #107789), and matching only the long form silently stopped
        // detecting the tab there.

        yield 'extended tab is moved to the end, TYPO3 v14 short form label' => [
            'tca' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
                                --div--;core.form.tabs:general,
                                    --palette--;;standard,
                                --div--;core.form.tabs:extended,
                                    extendedFieldA,
                                --div--;core.form.tabs:notes,
                                    rowDescription,
                            ',
                        ],
                    ],
                ],
            ],
            'definitionToAdd' => '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,',
            'types' => [],
            'excludeTypes' => [],
            'expected' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
--div--;core.form.tabs:general,
--palette--;;standard,
--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,
--div--;core.form.tabs:notes,rowDescription,
--div--;core.form.tabs:extended,extendedFieldA,',
                        ],
                    ],
                ],
            ],
        ];

        yield 'extended tab is moved to the end, TYPO3 v13 long form label' => [
            'tca' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                                    --palette--;;standard,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                                    extendedFieldA,
                                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                    rowDescription,
                            ',
                        ],
                    ],
                ],
            ],
            'definitionToAdd' => '--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,',
            'types' => [],
            'excludeTypes' => [],
            'expected' => [
                'pages' => [
                    'types' => [
                        1 => [
                            'showitem' => '
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
--palette--;;standard,
--div--;LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:pages.div.project, project_info, project_date,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,rowDescription,
--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,extendedFieldA,',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $tca
     * @param string $definitionToAdd
     * @param list<int|string> $types
     * @param list<int|string> $excludeTypes
     * @param array<string, mixed> $expected
     */
    #[DataProvider('returnsExpectedTcaArrayDataSet')]
    #[Test]
    public function returnsExpectedTcaArray(
        array $tca,
        string $definitionToAdd,
        array $types,
        array $excludeTypes,
        array $expected,
    ): void {
        $this->assertSame($expected, (new TcaManipulator())->addToPageTypesGeneralTab($tca, $definitionToAdd, $types, $excludeTypes));
    }
}
