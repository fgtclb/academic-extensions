<?php

declare(strict_types=1);

use FGTCLB\AcademicProjects\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$sourceString = static fn(string $icon): string => sprintf(
    'EXT:academic_projects/Resources/Public/Icons/CategoryTypes/%s.svg',
    GeneralUtility::underscoredToUpperCamelCase($icon)
);

$identifierString = static function (string $identifier) {
    return sprintf(
        'academic-project-%s',
        $identifier
    );
};

return [
    $identifierString(CategoryTypes::TYPE_COMPETENCE_FIELD) => [
        'provider' => SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_COMPETENCE_FIELD),
    ],
    $identifierString(CategoryTypes::TYPE_COOPERATION) => [
        'provider' => SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_COOPERATION),
    ],
    $identifierString(CategoryTypes::TYPE_DEPARTMENT) => [
        'provider' => SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_DEPARTMENT),
    ],
    $identifierString(CategoryTypes::TYPE_FUNDING_PARTNER) => [
        'provider' => SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_FUNDING_PARTNER),
    ],
];
