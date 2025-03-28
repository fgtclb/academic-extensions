<?php

declare(strict_types=1);

use Fgtclb\AcademicPersonsSync\Domain\Model\FrontendUser;

/*
 * This file is part of the "academic_persons_sync" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    FrontendUser::class => [
        'tableName' => 'fe_users',
    ],
];
