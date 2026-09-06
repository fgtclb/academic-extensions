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
    // Well-formed XML whose root element is not <svg>. The sanitiser throws on it, on
    // both core versions, and the provider has to answer with no markup rather than let
    // the exception out - see CurrentColorSvgIconProviderTest.
    'test-current-color-wrong-root' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_current_color_icons/Resources/Public/Icons/wrong-root.svg',
    ],
    // The same file through the core provider, for the comparison the tests draw.
    'test-current-color-arrow-image' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_current_color_icons/Resources/Public/Icons/arrow.svg',
    ],
];
