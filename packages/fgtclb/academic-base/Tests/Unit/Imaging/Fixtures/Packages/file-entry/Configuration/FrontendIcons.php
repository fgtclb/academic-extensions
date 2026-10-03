<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'example-generated' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:file_entry/Resources/Public/Icons/generated.svg',
    ],
];
