<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tx_academiccontacts4pages_domain_model_contact' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/tx_academiccontacts4pages_domain_model_contact.svg',
    ],
    'tx_academiccontacts4pages_domain_model_role' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/tx_academiccontacts4pages_domain_model_role.svg',
    ],
    'tx_academiccontacts4pages_domain_model_contact_pages' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/contactsforpages-icon.svg',
    ],
];
