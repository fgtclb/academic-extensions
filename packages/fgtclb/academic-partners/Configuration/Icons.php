<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'academic-partners' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/Extension.svg',
    ],
    'tx_academicpartners_domain_model_partnership' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/Partnership.svg',
    ],
    'tx_academicpartners_domain_model_role' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/Role.svg',
    ],
    'category_types.partners.collaboration_type' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic-partners/Resources/Public/Icons/CategoryTypes/CollaborationType.svg',
    ],
    'category_types.partners.partner_type' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic-partners/Resources/Public/Icons/CategoryTypes/PartnerType.svg',
    ],
    'category_types.partners.region' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic-partners/Resources/Public/Icons/CategoryTypes/Region.svg',
    ],
    'category_types.partners.sdg' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic-partners/Resources/Public/Icons/CategoryTypes/Sdg.svg',
    ],
];
