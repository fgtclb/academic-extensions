<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'academic_contacts4pages' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/Extension.svg',
    ],
    'tx_academiccontacts4pages_domain_model_contact' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/Extension.svg',
    ],
    'tx_academiccontacts4pages_domain_model_role' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/Role.svg',
    ],
    'tx_academiccontacts4pages_domain_model_contract' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/Contract.svg',
    ],
];
