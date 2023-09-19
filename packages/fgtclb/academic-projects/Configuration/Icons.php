<?php

declare(strict_types=1);

use FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes;

$sourceString = function (string $icon) {
    return sprintf(
        'EXT:academic_projects/Resources/Public/Icons/%s.svg',
        \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($icon)
    );
};

$identifierString = function (string $identifier) {
    return sprintf(
        'academic-project-%s',
        $identifier
    );
};

return [
    $identifierString(CategoryTypes::TYPE_SYSTEM_CATEGORY) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_SYSTEM_CATEGORY),
    ],
    $identifierString(CategoryTypes::TYPE_RESEARCH_TOPIC) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_RESEARCH_TOPIC),
    ],
    $identifierString(CategoryTypes::TYPE_DEPARTMENT) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_DEPARTMENT),
    ],
    $identifierString(CategoryTypes::TYPE_INSTITUTE) => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString(CategoryTypes::TYPE_INSTITUTE),
    ],
];
