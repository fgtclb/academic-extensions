<?php

declare(strict_types=1);

use FGTCLB\AcademicProjects\Enumeration\CategoryTypes;

$sourceString = function (string $icon) {
    return sprintf(
        'EXT:academic_projects/Resources/Public/Icons/CategoryTypes/%s.svg',
        \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($icon)
    );
};

$identifierString = function (string $identifier) {
    return sprintf(
        'academic-project-%s',
        $identifier
    );
};

return [
    $identifierString(CategoryTypes::TYPE_COMPETENCE_FIELD) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_COMPETENCE_FIELD),
    ],
    $identifierString(CategoryTypes::TYPE_COOPERATION) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_COOPERATION),
    ],
    $identifierString(CategoryTypes::TYPE_DEPARTMENT) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_DEPARTMENT),
    ],
    $identifierString(CategoryTypes::TYPE_FUNDING_PARTNER) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_FUNDING_PARTNER),
    ],
];
