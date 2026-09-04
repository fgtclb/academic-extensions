<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'test-current-color-arrow' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_current_color_icons/Resources/Public/Icons/arrow.svg',
    ],
    'test-current-color-scripted' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_current_color_icons/Resources/Public/Icons/scripted.svg',
    ],
    'test-current-color-missing' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_current_color_icons/Resources/Public/Icons/missing.svg',
    ],
    // The same file through the core provider, for the comparison the tests draw.
    'test-current-color-arrow-image' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_current_color_icons/Resources/Public/Icons/arrow.svg',
    ],
];
