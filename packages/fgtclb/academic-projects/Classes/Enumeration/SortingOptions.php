<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Enumeration;

/**
 * Sorting options for the projects list. Kept as a plain constant holder (not a
 * native PHP Enum) because the values are used directly as scalar query/order
 * strings; TYPO3\CMS\Core\Type\Enumeration was removed in TYPO3 v14.
 */
final class SortingOptions
{
    public const __default = self::SORT_BY_TITLE_ASC;
    public const SORT_BY_TITLE_ASC = 'title asc';
    public const SORT_BY_TITLE_DESC = 'title desc';
    public const SORT_BY_LASTUPDATED_ASC = 'lastUpdated asc';
    public const SORT_BY_LASTUPDATED_DESC = 'lastUpdated desc';
    public const SORT_BY_SORTING_ASC = 'sorting asc';
    public const SORT_BY_SORTING_DESC = 'sorting desc';
    public const SORT_BY_BUDGET_ASC = 'tx_academicprojects_budget asc';
    public const SORT_BY_BUDGET_DESC = 'tx_academicprojects_budget desc';
    public const SORT_BY_STARTDATE_ASC = 'tx_academicprojects_start_date asc';
    public const SORT_BY_STARTDATE_desc = 'tx_academicprojects_start_date desc';

    /**
     * Returns all sorting option constants (excluding the `__default` alias),
     * keyed by constant name. Replaces the removed
     * TYPO3\CMS\Core\Type\Enumeration::getConstants().
     *
     * @return array<string, string>
     */
    public static function getConstants(): array
    {
        $constants = [];
        foreach ((new \ReflectionClass(self::class))->getReflectionConstants() as $constant) {
            $value = $constant->getValue();
            if ($constant->getName() === '__default' || !is_string($value)) {
                continue;
            }
            $constants[$constant->getName()] = $value;
        }
        return $constants;
    }
}
