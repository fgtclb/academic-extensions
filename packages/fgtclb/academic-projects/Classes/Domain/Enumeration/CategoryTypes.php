<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;

class CategoryTypes extends Enumeration
{
    public const TYPE_SYSTEM_CATEGORY = 'system_category';

    public const TYPE_RESEARCH_TOPIC = 'research_topic';

    public const TYPE_DEPARTMENT = 'department';

    public const TYPE_INSTITUTE = 'institute';
}
