<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ProfileImageMetadataServiceTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function metadataUsesOnlyTheCurrentLanguageProfileAndItsFalReference(): void
    {
        $this->importCSVDataSet(
            __DIR__ . '/RecordSynchronizer/Fixtures/ProfileWithRelations.csv',
        );
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_reference');
        // Keep the profile's relation count at 1 while making the actual reference uid
        // distinct. Treating the count as a uid would update no reference at all.
        $connection->update('sys_file_reference', ['uid' => 37], ['uid' => 1]);
        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->insert('tx_academicpersons_domain_model_profile', [
                'uid' => 501,
                'pid' => 100,
                'sys_language_uid' => 1,
                'l10n_parent' => 1,
                'first_name' => 'Erika',
                'last_name' => 'Beispiel',
                'image' => 1,
            ]);
        $connection->insert('sys_file_reference', [
            'uid' => 38,
            'pid' => 100,
            'sys_language_uid' => 1,
            'l10n_parent' => 37,
            'uid_local' => 1,
            'uid_foreign' => 501,
            'tablenames' => 'tx_academicpersons_domain_model_profile',
            'fieldname' => 'image',
            'sorting_foreign' => 1,
        ]);
        $metadata = $this->get(ProfileImageMetadataService::class)->updateForProfileUid(501);

        $this->assertSame(
            ['alternative' => 'Erika Beispiel', 'title' => 'Erika Beispiel'],
            $metadata,
        );
        $rows = $connection
            ->executeQuery(
                'SELECT uid, title, alternative FROM sys_file_reference WHERE uid IN (?, ?) ORDER BY uid',
                [37, 38],
            )
            ->fetchAllAssociative();
        $this->assertSame(
            [
                ['uid' => 37, 'title' => '', 'alternative' => ''],
                ['uid' => 38, 'title' => 'Erika Beispiel', 'alternative' => 'Erika Beispiel'],
            ],
            array_map(
                static fn(array $row): array => [
                    'uid' => (int)$row['uid'],
                    'title' => (string)$row['title'],
                    'alternative' => (string)$row['alternative'],
                ],
                $rows,
            ),
        );
        $this->assertSame(
            ['title' => 'Erika Beispiel', 'alternative' => 'Erika Beispiel'],
            $this->getConnectionPool()
                ->getConnectionForTable('sys_file_metadata')
                ->executeQuery('SELECT title, alternative FROM sys_file_metadata WHERE file = ?', [1])
                ->fetchAssociative(),
        );
    }
}
