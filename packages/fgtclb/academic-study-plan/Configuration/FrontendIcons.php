<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/*
 * The controls of the study plan element, registered in the frontend icon registry of
 * EXT:academic_base and rendered with its `ab:icon` ViewHelper by the partials
 * `StudyPlan/Semester.html` and `StudyPlan/ModuleDialog.html`. The backend never shows
 * them, so they are not in `Configuration/Icons.php`. A site package that depends on this
 * extension replaces one by registering its identifier in its own
 * `Configuration/FrontendIcons.php`.
 *
 * Drawn in `currentColor` so they take the colour of the surrounding text, and asked for
 * with the `inline` markup. The shipped stylesheet switches the plus and the minus glyph
 * through the classes `icon-academic-study-plan-plus` and `icon-academic-study-plan-minus`
 * of their wrappers.
 */
return [
    'academic-study-plan-plus' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/plus.svg',
    ],
    'academic-study-plan-minus' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/minus.svg',
    ],
    'academic-study-plan-close' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/close.svg',
    ],
];
