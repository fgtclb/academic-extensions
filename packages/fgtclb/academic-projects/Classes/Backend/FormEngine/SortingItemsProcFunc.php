<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Backend\FormEngine;

use FGTCLB\AcademicProjects\Enumeration\SortingOptions;
use TYPO3\CMS\Core\Information\Typo3Version;

class SortingItemsProcFunc
{
    /**
     * @param array<string, mixed> $params
     */
    public function itemsProcFunc(array &$params): void
    {
        $typo3MajorVersion = (new Typo3Version())->getMajorVersion();
        $selectLabelKey = ($typo3MajorVersion >= 12) ? 'label' : 0;
        $selectValueKey = ($typo3MajorVersion >= 12) ? 'value' : 1;

        foreach (SortingOptions::getConstants() as $value) {
            $label = 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:flexform.sorting.' . str_replace(' ', '.', $value);
            $params['items'][] = [
                $selectLabelKey => $label,
                $selectValueKey => $value,
            ];
        }
    }
}
