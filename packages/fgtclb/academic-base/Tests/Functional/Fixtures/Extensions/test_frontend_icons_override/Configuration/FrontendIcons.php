<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

return [
    'test-frontend-replaced' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons_override/Resources/Public/Icons/site-replaced.svg',
    ],
    'default-not-found' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons_override/Resources/Public/Icons/site-not-found.svg',
    ],
];
