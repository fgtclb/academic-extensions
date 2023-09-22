<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_bite_jobs" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicBiteJobs\Migrations\Mysql;

use AndreasWolf\Uuid\UuidResolverFactory;
use Doctrine\DBAL\Schema\Schema;
use KayStrobach\Migrations\Migration\AbstractDataHandlerMigration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Version202309221005 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $this->dataMap = [
            'sys_template' => $this->setupTypoScript(),
            'tt_content' => $this->addListPlugin(),
        ];

        parent::up($schema);
    }
    public function setupTypoScript(): array
    {

        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $rootPageUid = $uuidResolver->getUidForUuid('e4a09d09-79ec-471f-bb1a-d007dfc17350');

        $data = [
            'NEW1695372443' => [
                'pid' => $rootPageUid,
                'title' => 'Academic Bite Jobs',
                'root' => 1,
                'include_static_file' => implode(',', [
                    'EXT:bootstrap_package/Configuration/TypoScript/',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/',
                ]),
                'config' => '',
            ],
        ];

        return $data;
    }
    public function addListPlugin(): array
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $rootPageUid = $uuidResolver->getUidForUuid('e4a09d09-79ec-471f-bb1a-d007dfc17350');

        $data = [
            'NEW1688731717' => [
                'uuid' => '867fdf58-c3b5-4830-a02c-3f6fdcdb2eeb',
                'pid' => $rootPageUid,
                'CType' => 'list',
                'list_type' => 'academicbitejobs_list',
            ],
        ];

        return $data;
    }

}
