<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    'example-phone' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:second/Resources/Public/Icons/phone.png',
    ],
];
