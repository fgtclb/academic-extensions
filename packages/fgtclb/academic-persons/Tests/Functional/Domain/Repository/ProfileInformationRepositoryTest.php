<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileInformationRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ProfileInformationRepositoryTest extends AbstractAcademicPersonsTestCase
{
    /**
     * The three date columns are native SQL `DATE` values, so the repository
     * hands back `\DateTime` objects rather than the four digit integers the
     * columns used to hold. They are compared as `Y-m-d` strings here, which
     * is exactly what a `dbType => 'date'` column stores.
     *
     * The month and day the fixture picks - the first of January for a plain
     * or starting year, the last of December for an ending one - are the
     * fixture's own choice. The extension applies no such rule: a date column
     * carries whatever day was written to it.
     */
    #[Test]
    public function findByProfileAndTypeReturnsProfileInformationRespectingSortingFieldValues(): void
    {
        $assertFields = [
            'uid',
            'pid',
            'type',
            'sorting',
            'title',
            'date',
            'date_start',
            'date_end',
        ];
        $expected = [
            [
                'uid' => 1,
                'pid' => 20,
                'type' => 'type_1',
                'sorting' => 1,
                'title' => 'Type 1 - UID 1 Pos #1',
                'date' => '2020-01-01',
                'date_start' => null,
                'date_end' => null,
            ],
            [
                'uid' => 4,
                'pid' => 20,
                'type' => 'type_1',
                'sorting' => 2,
                'title' => 'Type 1 - UID 3 Pos #2',
                'date' => '2020-01-01',
                'date_start' => null,
                'date_end' => null,
            ],
            [
                'uid' => 3,
                'pid' => 20,
                'type' => 'type_1',
                'sorting' => 3,
                'title' => 'Type 1 - UID 2 Pos #3',
                'date' => null,
                'date_start' => '2019-09-01',
                'date_end' => '2020-08-31',
            ],
        ];
        $profileRepository = GeneralUtility::makeInstance(ProfileRepository::class);
        $profileInformationRepository = GeneralUtility::makeInstance(ProfileInformationRepository::class);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationRepository/Import/profilesWithSortingFieldsNotMatchingUidOrder.csv');
        $profile = $profileRepository->findByUid(1);
        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertSame($profile->getUid(), 1);
        $this->assertSame($profile->getPid(), 20);

        $profileInformationTypes = $profileInformationRepository->findByProfileAndType($profile, 'type_1');
        $this->assertCount(3, $profileInformationTypes);
        $records = [];
        $normalizedData = [];
        foreach ($profileInformationTypes as $profileInformation) {
            if ($profileInformation->getUid() === null) {
                continue;
            }
            $normalizedData[$profileInformation->getUid()] = [
                'uid' => $profileInformation->getUid(),
                'pid' => $profileInformation->getPid(),
                'sorting' => $profileInformation->getSorting(),
                'type' => $profileInformation->getType(),
                'title' => $profileInformation->getTitle(),
                'date' => $profileInformation->getDate()?->format('Y-m-d'),
                'date_start' => $profileInformation->getDateStart()?->format('Y-m-d'),
                'date_end' => $profileInformation->getDateEnd()?->format('Y-m-d'),
            ];
        }
        $tableName = 'tx_academicpersons_domain_model_profile_information';
        $this->assertMatchingArray($tableName, $expected, $normalizedData, $assertFields);
    }
}
