<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'example-phone' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:first/Resources/Public/Icons/phone.svg',
        'spinning' => true,
    ],
    'example-only-first' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:first/Resources/Public/Icons/only-first.svg',
    ],
];
