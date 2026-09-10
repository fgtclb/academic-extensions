<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The icons of this extension: Font Awesome Free solid, drawn in `currentColor` and
 * inlined by the provider of EXT:academic_base, so they take the colour of the
 * surrounding text in both backend colour schemes. Licence and origin of the files:
 * Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-<extkey>-<group>-<name>`, files `Icons/<group>/<name>.svg`:
 * `plugin` for the content element (TCA and new content element wizard), `record` for
 * the TCA record types. The frontend controls of the element - expand, collapse and
 * close - are the shared action icons of EXT:academic_base and not registered here.
 * A project replaces one of them by registering the same identifier in its own
 * Configuration/Icons.php.
 */
return [
    'tx-academicstudyplan-plugin-study-plan' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/plugin/study-plan.svg',
    ],
    'tx-academicstudyplan-record-category' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/record/category.svg',
    ],
    'tx-academicstudyplan-record-semester' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/record/semester.svg',
    ],
    'tx-academicstudyplan-record-module' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/record/module.svg',
    ],
];
