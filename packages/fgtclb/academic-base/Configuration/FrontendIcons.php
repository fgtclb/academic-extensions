<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/**
 * The icons of the frontend icon registry, see the configuration chapter of the
 * manual. academic_base registers only the placeholder an unknown identifier renders,
 * the drawing TYPO3 shows for an unknown icon. A site package that depends on
 * academic_base replaces it by registering `default-not-found` in its own file.
 */
return [
    'default-not-found' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/default/default-not-found.svg',
    ],
];
