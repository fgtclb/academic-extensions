<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/**
 * The frontend icons: an action icon drawn from a file of its own, named after the
 * identifier, and an info icon drawn from a file of the shared set of academic_base.
 */
return [
    'tx-testiconrules-action-sort' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_icon_rules/Resources/Public/Icons/action/sort.svg',
    ],
    'tx-testiconrules-info-phone' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/phone.svg',
    ],
];
