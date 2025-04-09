<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Backend\FormEngine;

use FGTCLB\AcademicProjects\Enumeration\SortingOptions;

class SortingItemsProcFunc
{
    /**
     * @param array<string, mixed> $params
     */
    public function itemsProcFunc(array &$params): void
    {
        foreach (SortingOptions::getConstants() as $value) {
            $label = 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:flexform.sorting.' . str_replace(' ', '.', $value);
            $params['items'][] = [
                'label' => $label,
                'value' => $value,
            ];
        }
    }
}
