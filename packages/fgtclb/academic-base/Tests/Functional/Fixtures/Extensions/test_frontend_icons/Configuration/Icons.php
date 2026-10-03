<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'test-frontend-both' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/arrow.svg',
    ],
    // A backend icon only. The frontend registry does not know it.
    'test-backend-only' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/arrow.svg',
    ],
];
