<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;

return [
    // A site package replacing a shared icon: same identifier, a file of its own. The
    // extension key sorts after academic_base, so the entry is read after the original.
    'tx-academicbase-info-phone' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icon_api/Resources/Public/Icons/override.svg',
    ],
    // The identifier shapes category_types registers for a type and a group.
    'category_types.test.type' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icon_api/Resources/Public/Icons/star.svg',
    ],
    'category_types_group.test' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icon_api/Resources/Public/Icons/star.svg',
    ],
    // Inlined by the core SVG provider for the "inline" markup: served.
    'test-frontend-icon-api-core-svg' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_frontend_icon_api/Resources/Public/Icons/star.svg',
    ],
    // Providers that do not inline a file: refused.
    'test-frontend-icon-api-bitmap' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:test_frontend_icon_api/Resources/Public/Icons/bitmap.png',
    ],
    'test-frontend-icon-api-sprite' => [
        'provider' => SvgSpriteIconProvider::class,
        'sprite' => 'EXT:test_frontend_icon_api/Resources/Public/Icons/star.svg#star',
    ],
    // Registered, but longer than the 100 characters an identifier may have.
    'test-frontend-icon-api-' . str_repeat('x', 80) => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icon_api/Resources/Public/Icons/star.svg',
    ],
];
