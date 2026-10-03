<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'test-frontend-current-color' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/arrow.svg',
    ],
    'test-frontend-svg' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/coloured.svg',
    ],
    'test-frontend-bitmap' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/bitmap.png',
    ],
    // No provider: the registry detects the SVG provider from the file name, as
    // core does for Configuration/Icons.php.
    'test-frontend-detected' => [
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/coloured.svg',
    ],
    // Neither provider nor source: skipped, as core skips it.
    'test-frontend-without-source' => [
        'spinning' => true,
    ],
    'test-frontend-missing' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/missing.svg',
    ],
    // A copy of the scripted file of test_current_color_icons: both registries have
    // to sanitise it the same way.
    'test-frontend-scripted' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/scripted.svg',
    ],
    'test-frontend-spinning' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/arrow.svg',
        'spinning' => true,
        'bidi' => true,
    ],
    // tests/frontend-icons-override replaces it.
    'test-frontend-replaced' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/original.svg',
    ],
    // Registered identically in Configuration/Icons.php, so a template can render it
    // from both registries.
    'test-frontend-both' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/arrow.svg',
    ],
    // ContributeFrontendIcons contributes it as well. The file wins.
    'test-frontend-contributed-overridden' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/file-wins.svg',
    ],
];
