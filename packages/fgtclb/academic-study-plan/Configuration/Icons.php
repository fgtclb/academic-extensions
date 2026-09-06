<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'academic-study-plan' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/Extension.svg',
    ],
    /*
     * The record icons of the three tables this extension ships. They are registered
     * with the provider of EXT:academic_base, which inlines the file in both markups
     * instead of rendering an <img>. An <img> is opaque to CSS and keeps the colours
     * of its file, so a record icon drawn in a dark ink stays dark on the dark cards
     * of the backend colour scheme. Inlined and drawn in `currentColor` it follows the
     * text colour.
     */
    'academic-study-plan-category' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/category.svg',
    ],
    'academic-study-plan-semester' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/semester.svg',
    ],
    'academic-study-plan-module' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/module.svg',
    ],
    // Frontend controls of the study plan element, unlike the record icons above:
    // drawn in `currentColor` so they take the colour of the surrounding text.
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
