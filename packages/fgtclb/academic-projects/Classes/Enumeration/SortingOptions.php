<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;

final class SortingOptions extends Enumeration
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
}
