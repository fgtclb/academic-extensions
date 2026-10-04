<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

// The way a site package replaces a glyph of academic_study_plan for the frontend.
return [
    'academic-study-plan-close' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_study_plan_icons/Resources/Public/Icons/replaced.svg',
    ],
];
