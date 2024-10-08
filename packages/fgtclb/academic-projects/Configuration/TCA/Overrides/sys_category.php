<?php

declare(strict_types=1);

use FGTCLB\AcademicProjects\Enumeration\CategoryTypes;

(static function (): void {
    $ll = static function (string $label) {
        return sprintf(
            'LLL:EXT:academic_projects/Resources/Private/Language/locallang.xlf:sys_category.type.%s',
            $label
        );
    };

    $iconType = static function (string $iconType) {
        return sprintf(
            'academic-project-%s',
            $iconType
        );
    };

    // Create new select item group for category types
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
        'sys_category',
        'type',
        'projects',
        'LLL:EXT:academic_projects/Resources/Private/Language/locallang.xlf:sys_category.group.projects',
    );

    // Add new category types to the group

    $addItems = [
        [
            $ll(CategoryTypes::TYPE_COMPETENCE_FIELD),
            CategoryTypes::TYPE_COMPETENCE_FIELD,
            $iconType(CategoryTypes::TYPE_COMPETENCE_FIELD),
            'projects',
        ],
        [
            $ll(CategoryTypes::TYPE_COOPERATION),
            CategoryTypes::TYPE_COOPERATION,
            $iconType(CategoryTypes::TYPE_COOPERATION),
            'projects',
        ],
        [
            $ll(CategoryTypes::TYPE_DEPARTMENT),
            CategoryTypes::TYPE_DEPARTMENT,
            $iconType(CategoryTypes::TYPE_DEPARTMENT),
            'projects',
        ],
        [
            $ll(CategoryTypes::TYPE_FUNDING_PARTNER),
            CategoryTypes::TYPE_FUNDING_PARTNER,
            $iconType(CategoryTypes::TYPE_FUNDING_PARTNER),
            'projects',
        ],
    ];

    foreach ($addItems as $addItem) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'sys_category',
            'type',
            $addItem
        );
    }

    // Add icons for the new category types

    $sysCategoryTypesTcaTypeIconOverrides = [
        'ctrl' => [
            'typeicon_classes' => [
                CategoryTypes::TYPE_COMPETENCE_FIELD
                => $iconType(CategoryTypes::TYPE_COMPETENCE_FIELD),
                CategoryTypes::TYPE_COOPERATION
                => $iconType(CategoryTypes::TYPE_COOPERATION),
                CategoryTypes::TYPE_DEPARTMENT
                => $iconType(CategoryTypes::TYPE_DEPARTMENT),
                CategoryTypes::TYPE_FUNDING_PARTNER
                => $iconType(CategoryTypes::TYPE_FUNDING_PARTNER),
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['sys_category'],
        $sysCategoryTypesTcaTypeIconOverrides
    );
})();
