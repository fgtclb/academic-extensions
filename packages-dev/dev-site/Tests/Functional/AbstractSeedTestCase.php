<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use FGTCLB\AcademicsDevSite\Tests\Functional\Support\SeedDefinition;
use SBUERK\DataFactory\Seeding\DataHandling\FileReferenceSeeder;
use SBUERK\DataFactory\Seeding\DataHandling\FileSeeder;
use SBUERK\DataFactory\Seeding\DataHandling\ScenarioSeeder;
use SBUERK\DataFactory\Seeding\DataHandling\ScenarioSeedResult;
use SBUERK\DataFactory\Seeding\Parser\SeedDefinitionParser;
use SBUERK\DataFactory\Seeding\Scenario\ScenarioComposer;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Imports the seed set of this package into a functional test instance.
 *
 * Every check of the seed starts here, and the reason is the one correction the
 * probe of ACE-460 produced: a manifest counted from the YAML is wrong before it
 * is written down, because `DataHandler` writes `sys_file_reference` rows for
 * the translation of a page and of a profile that no `references:` entry names.
 * The only thing that knows what the seed produces is the seed, imported.
 *
 * The import runs through the same services `data-factory:import` runs through -
 * `SeedDefinitionParser`, `ScenarioComposer`, `ScenarioSeeder` - and not through
 * the command, because the command is a console application with an exit code
 * and this is a test that wants an exception.
 */
abstract class AbstractSeedTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-fluid-styled-content',
        'typo3/cms-felogin',
        'typo3/cms-rte-ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'georgringer/numbered-pagination',
        'sbuerk/data-factory',
        'fgtclb/category-types',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'fgtclb/academic-persons-edit',
        'fgtclb/academic-persons-sync',
        'fgtclb/academic-contacts4pages',
        'fgtclb/academic-jobs',
        'fgtclb/academic-bite-jobs',
        'fgtclb/academic-partners',
        'fgtclb/academic-programs',
        'fgtclb/academic-projects',
        'fgtclb/academic-study-plan',
        // Loaded although it ships no PHP: `SeedDefinitionParser::parseFile()`
        // resolves through `GeneralUtility::getFileAbsFileName()`, which answers
        // an `EXT:academics_dev_site/...` path only for an *active* package.
        'fgtclb/academics-monorepo-dev-site',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/AdminBackendUser.csv');
        $this->createDefaultFileStorage();
    }

    /**
     * Imports the seed set, as `data-factory:import academics-instance` would.
     */
    protected function importSeed(): ScenarioSeedResult
    {
        $definition = (new SeedDefinitionParser())->parseFile(SeedDefinition::CONFIG_FILE);
        $factory = (new ScenarioComposer())->compose($definition, 0);

        return (new ScenarioSeeder(
            new FileSeeder(GeneralUtility::makeInstance(StorageRepository::class)),
            new FileReferenceSeeder(GeneralUtility::makeInstance(ConnectionPool::class)),
        ))->seed($definition, $factory, $this->setUpBackendUser(1));
    }

    /**
     * A functional test instance has a `fileadmin/` folder and no
     * `sys_file_storage` record - a real installation gets that one from
     * `typo3 setup`. Without it the file pass of the import has nowhere to put
     * the twenty seven files of the seed.
     */
    private function createDefaultFileStorage(): void
    {
        GeneralUtility::makeInstance(StorageRepository::class)
            ->createLocalStorage('fileadmin', 'fileadmin/', 'relative', 'Seed check storage', true);
    }
}
