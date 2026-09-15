<?php

declare(strict_types=1);

return [
    // Icon of the study plan content element. A placeholder until a dedicated icon
    // is drawn; the extension icon of the TER and the extension manager is Extension.svg.
    'academic-study-plan' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/content-element.svg',
    ],
    'academic-study-plan-category' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/category.svg',
    ],
    'academic-study-plan-semester' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/semester.svg',
    ],
    'academic-study-plan-module' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/module.svg',
    ],
    // Frontend controls of the study plan element, unlike the record icons above:
    // drawn in `currentColor` so they take the colour of the surrounding text.
    'academic-study-plan-plus' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/plus.svg',
    ],
    'academic-study-plan-minus' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/minus.svg',
    ],
    'academic-study-plan-close' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:academic_study_plan/Resources/Public/Icons/close.svg',
    ],
];
