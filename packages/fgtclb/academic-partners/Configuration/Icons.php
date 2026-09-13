<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The icons of this extension: Font Awesome Free solid, drawn in `currentColor` and
 * inlined by the provider of EXT:academic_base in both markups, so they take the colour
 * of the surrounding text in both backend colour schemes and in the frontend. Licence
 * and origin of the own files: Resources/Public/Icons/LICENSE-font-awesome.txt; the two
 * record icons use the shared `info` glyphs of EXT:academic_base.
 *
 * Identifiers follow `tx-<extkey>-<group>-<name>`: `plugin` for the four content
 * elements, `doktype` for the academic partner page type, `record` for the tables of
 * this extension. Page type and content elements share one drawing but not one
 * identifier, so a project can replace either by registering it again in its own
 * Configuration/Icons.php. The category type icons are registered by EXT:category_types
 * from Configuration/CategoryTypes.yaml.
 */
return [
    'tx-academicpartners-doktype-partner' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/plugin/partners.svg',
    ],
    'tx-academicpartners-plugin-partners' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/plugin/partners.svg',
    ],
    'tx-academicpartners-record-partnership' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/partnership.svg',
    ],
    'tx-academicpartners-record-role' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/role.svg',
    ],
];
