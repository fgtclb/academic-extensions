<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service\RecordSynchronizer;

use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;
use FGTCLB\AcademicPersons\Service\RecordSynchronizer;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\TcaHelperMethodsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Entity\Site;

final class RecordSyncronizerAgainstFixtureExtensionTest extends AbstractAcademicPersonsTestCase
{
    use TcaHelperMethodsTrait;

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-recordsyncronizer-fixtures';
        $this->testExtensionsToLoad = array_unique($this->testExtensionsToLoad);
        parent::setUp();
        $this->createTCABackup(force: false);
    }

    protected function tearDown(): void
    {
        $this->restoreTCABackup(throwExceptionWhenBackupDoesNotExists: false);
        parent::tearDown();
    }

    public static function verifyDatabaseStateAfterExecutingSynchronizeDataSets(): \Generator
    {
        $site = self::createBasicSite();
        yield 'simple table without relations, not all languages: create translation' => [
            'databaseFixtureFile' => 'simple-table/create-translation.csv',
            'site' => $site,
            'allowedLanguageIds' => [1],
            'tableName' => 'simple_table',
            'uid' => 1,
        ];
        yield 'simple table without relations, not all languages: update translation' => [
            'databaseFixtureFile' => 'simple-table/update-translation.csv',
            'site' => $site,
            'allowedLanguageIds' => [1],
            'tableName' => 'simple_table',
            'uid' => 1,
        ];
    }

    /**
     * @param string $databaseFixtureFile
     * @param Site $site
     * @param int[] $allowedLanguageIds
     * @param string $tableName
     * @param int $uid
     */
    #[DataProvider(methodName: 'verifyDatabaseStateAfterExecutingSynchronizeDataSets')]
    #[Test]
    public function verifyDatabaseStateAfterExecutingSynchronize(
        string $databaseFixtureFile,
        Site $site,
        array $allowedLanguageIds,
        string $tableName,
        int $uid,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/fixture_extension/import/' . $databaseFixtureFile);
        $subject = $this->createRecordSynchronizer();
        $subject->synchronize(SynchronizerContext::create(
            recordSyncronizer: $subject,
            site: $site,
            allowedLanguageIds: $allowedLanguageIds,
            tableName: $tableName,
            uid: $uid,
        ));
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/fixture_extension/assert/' . $databaseFixtureFile);
    }

    private function createRecordSynchronizer(): RecordSynchronizer
    {
        return $this->get(RecordSynchronizer::class);
    }

    private static function createBasicSite(): Site
    {
        return new Site(
            'acme',
            1,
            [
                'languages' => [
                    0 => [
                        'languageId' => 0,
                        'title' => 'Default',
                        'navigationTitle' => '',
                        'flag' => 'us',
                        'locale' => 'en_US.UTF-8',
                    ],
                    1 => [
                        'languageId' => 1,
                        'title' => 'German',
                        'navigationTitle' => '',
                        'flag' => 'de',
                        'locale' => 'de_DE.UTF-8',
                    ],
                    2 => [
                        'languageId' => 2,
                        'title' => 'French',
                        'navigationTitle' => '',
                        'flag' => 'fr',
                        'locale' => 'fr_FR.UTF-8',
                    ],
                ],
            ],
            null,
        );
    }
}
