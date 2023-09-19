<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Backend\FormEngine;

use FGTCLB\AcademicProjects\Domain\Enumeration\SortingOptions;

class SortingItemsProcFunc
{
    /**
     * @param array $params
     */
    public function itemsProcFunc(&$params): void
    {
        foreach (SortingOptions::getConstants() as $value) {
            $label = 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:flexform.sorting.' . str_replace(' ', '.', $value);
            $params['items'][] = [$label, $value];
        }
    }
}
