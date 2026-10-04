<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/**
 * The backend icons: a record icon drawn from a file of its own, a record icon drawn
 * from a file of the shared set of academic_base, and a content element and a page
 * type sharing one file.
 */
return [
    'tx-testiconrules-record-item' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_icon_rules/Resources/Public/Icons/record/item.svg',
    ],
    'tx-testiconrules-record-role' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/role.svg',
    ],
    'tx-testiconrules-plugin-items' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_icon_rules/Resources/Public/Icons/plugin/items.svg',
    ],
    'tx-testiconrules-doktype-item-page' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_icon_rules/Resources/Public/Icons/plugin/items.svg',
    ],
];
