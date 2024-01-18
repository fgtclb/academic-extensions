<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;

final class CategoryTypes extends Enumeration
{
    public const TYPE_COMPETENCE_FIELD = 'competence_field';

    public const TYPE_COOPERATION = 'cooperation';

    public const TYPE_DEPARTMENT = 'department';

    public const TYPE_FUNDING_PARTNER = 'funding_partner';
}
