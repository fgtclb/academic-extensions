<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The backend icons of this extension: Font Awesome Free solid, drawn in `currentColor`
 * and inlined by the provider of EXT:academic_base in both markups, so they take the
 * colour of the surrounding text in both backend colour schemes. Licence and origin of
 * the files: Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-<extension key without underscores>-<group>-<name>`, files
 * `Icons/<group>/<name>.svg`: `plugin` for the content element (TCA and new content
 * element wizard), `record` for the three record types. The extension icon of the TER
 * and the extension manager is Extension.svg.
 *
 * The controls of the element in the frontend, expand, collapse and close, are the
 * shared action icons of EXT:academic_base in its frontend icon registry, so this
 * extension registers no frontend icon of its own. A site package replaces one of them,
 * for every academic extension that renders it, in its own
 * Configuration/FrontendIcons.php.
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
