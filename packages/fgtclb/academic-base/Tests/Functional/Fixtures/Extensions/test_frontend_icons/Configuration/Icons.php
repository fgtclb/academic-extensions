<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;

return [
    // A project override of a shared icon: same identifier, a file of its own. The
    // extension depends on academic_base, so this entry is merged after the original.
    'tx-academicbase-info-phone' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/override.svg',
    ],
    // Allowed prefix, but registered as deprecated: refused, without a deprecation.
    'tx-academictest-action-deprecated' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg',
        'deprecated' => [
            'since' => 'academic_base 3.0',
            'until' => 'academic_base 4.0',
            'replacement' => 'tx-academicbase-action-add',
        ],
    ],
    // Allowed prefix, inlined by the core SVG provider for the "inline" markup: served.
    'tx-academictest-action-core-svg' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg',
    ],
    // Allowed prefix, but providers that do not inline a file: refused.
    'tx-academictest-action-bitmap' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg',
    ],
    'tx-academictest-action-sprite' => [
        'provider' => SvgSpriteIconProvider::class,
        'sprite' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg#star',
    ],
    // Allowed prefix, but longer than the 100 characters an identifier may have.
    'tx-academictest-action-' . str_repeat('x', 80) => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg',
    ],
    // Allowed through the listener of this extension only.
    'tx-testproject-action-star' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg',
    ],
    // Not allowed by any prefix.
    'tx-testforeign-action-star' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg',
    ],
];
