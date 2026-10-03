<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'example-neither' => [
        'spinning' => true,
    ],
    'example-empty-source' => [
        'source' => '',
    ],
    'example-not-an-array' => SvgIconProvider::class,
    'example-kept' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:skipping/Resources/Public/Icons/kept.svg',
    ],
];
