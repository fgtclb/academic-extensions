<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/*
 * The record icons of the two tables this extension ships are registered with the
 * provider of EXT:academic_base, which inlines the file in both markups instead of
 * rendering an <img>. An <img> is opaque to CSS and keeps the colours of its file,
 * so a record icon drawn in a dark ink stays dark on the dark cards of the backend
 * colour scheme. Inlined and drawn in `currentColor` it follows the text colour.
 */
return [
    'academic_contacts4pages' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/Extension.svg',
    ],
    'tx_academiccontacts4pages_domain_model_contact' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/Extension.svg',
    ],
    'tx_academiccontacts4pages_domain_model_role' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/Role.svg',
    ],
    'tx_academiccontacts4pages_domain_model_contract' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/Contract.svg',
    ],
];
