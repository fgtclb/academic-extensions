<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

// A replacement left in the file of the backend registry, which the frontend does not read.
return [
    'academic-study-plan-plus' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_study_plan_icons/Resources/Public/Icons/replaced.svg',
    ],
];
