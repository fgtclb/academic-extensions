<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

// The way a site package replaces a shared glyph of academic_base the study plan renders,
// for the frontend.
return [
    'tx-academicbase-action-close' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_study_plan_icons/Resources/Public/Icons/replaced.svg',
    ],
];
