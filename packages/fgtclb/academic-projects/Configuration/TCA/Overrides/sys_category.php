<?php

declare(strict_types=1);

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
            $llBackendType(\FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_COMPETENCE_FIELD),
            \FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_COMPETENCE_FIELD,
            $iconType(\FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_COMPETENCE_FIELD),
            'projects',
        ],
        [
            $llBackendType(\FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_COOPERATION),
            \FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_COOPERATION,
            $iconType(\FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_COOPERATION),
            'projects',
        ],
        [
            $llBackendType(\FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_DEPARTMENT),
            \FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_DEPARTMENT,
            $iconType(\FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_DEPARTMENT),
            'projects',
        ],
        [
            $llBackendType(\FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_FUNDING_PARTNER),
            \FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_FUNDING_PARTNER,
            $iconType(\FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes::TYPE_FUNDING_PARTNER),
            'projects',
        ],
    ];

    // create new group
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
        'sys_category',
        'type',
        'projects',
        'LLL:EXT:academic_projects/Resources/Private/Language/locallang.xlf:sys_category.group.projects',
    );

    foreach ($addItems as $addItem) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'sys_category',
            'type',
            $addItem
        );
    }

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['sys_category'],
        $sysCategoryTypesTcaTypeIconOverrides
    );
})();
