<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_bite_jobs" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicBiteJobs\Migrations\Mysql;

use Doctrine\DBAL\Schema\Schema;
use KayStrobach\Migrations\Migration\AbstractDataHandlerMigration;

final class Version202309221000 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $this->dataMap = [
            'pages' => [
                'NEW123' => [
                    'uuid' => 'e4a09d09-79ec-471f-bb1a-d007dfc17350',
                    'pid' => 0,
                    'doktype' => 1,
                    'title' => 'Academic Bite Jobs',
                    'slug' => '/',
                    'is_siteroot' => 1,
                    'hidden' => 0,
                ],
            ],
        ];

        parent::up($schema);
    }
}
