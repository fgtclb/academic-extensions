<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use FGTCLB\AcademicsDevSite\Tests\Functional\Support\SeedDefinition;
use SBUERK\DataFactory\Seeding\DataHandling\ScenarioSeeder;
use SBUERK\DataFactory\Seeding\DataHandling\ScenarioSeedResult;
use SBUERK\DataFactory\Seeding\DataHandling\SiteConfigurationWriterInterface;
use SBUERK\DataFactory\Seeding\Parser\SeedDefinitionParser;
use SBUERK\DataFactory\Seeding\Scenario\ScenarioComposer;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
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
        // TYPO3 v12 reaches "BackendUtility::getLanguageService()" while writing
        // records and that method has a non-nullable return type, so an unset
        // "$GLOBALS['LANG']" is a TypeError rather than a missing label. TYPO3 v13
        // sets one up on its own; setting it here costs nothing there.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $this->createDefaultFileStorage();
    }

    /**
     * Imports the seed set, as `data-factory:import academics-instance` would.
     */
    protected function importSeed(): ScenarioSeedResult
    {
        $this->adoptTheCommittedSiteConfigurations();

        // Through the container and not with "new": on this branch
        // "SeedDefinitionParser" takes a YAML loader whose constructor differs
        // between TYPO3 v12 and v13, and the package registers the right one for
        // the running version itself. Building the parser by hand here would mean
        // repeating that decision, in a test, from the outside.
        $definition = $this->get(SeedDefinitionParser::class)->parseFile(SeedDefinition::configFile());
        $factory = $this->get(ScenarioComposer::class)->compose($definition, 0);

        return $this->get(ScenarioSeeder::class)->seed($definition, $factory, $this->setUpBackendUser(1));
    }

    /**
     * The site configurations of the development instance of the running core
     * version, copied into this test instance before the import.
     *
     * They are not decoration. `DataHandler` builds the prefix of a translated
     * field - `[Translate to German:]` - from the title of the site language the
     * record is translated into, and with no site there is no language and the
     * prefix comes out as `[Translate to :]`. The seed writes that prefix into
     * every translated `sys_file_reference`, so an import without the sites
     * produces content a real installation never produces, and the manifest
     * generated from it disagrees with the committed snapshot on 22 rows.
     *
     * Copied rather than built, so that a site setting changed in the instance
     * reaches this measurement without anyone remembering to mirror it here.
     */
    private function adoptTheCommittedSiteConfigurations(): void
    {
        $source = sprintf(
            '%s/core-%d/config/sites',
            dirname(__DIR__, 4),
            (new Typo3Version())->getMajorVersion(),
        );
        // Through the seeder's own interface and not through the core class:
        // "TYPO3\CMS\Core\Configuration\SiteWriter" arrived in TYPO3 v13, and
        // this branch also supports v12, where the same job belongs to
        // "SiteConfiguration". "sbuerk/data-factory" already carries one
        // implementation per core version behind this interface.
        $writer = $this->get(SiteConfigurationWriterInterface::class);

        foreach ((array)glob($source . '/*', GLOB_ONLYDIR) as $site) {
            $identifier = basename((string)$site);
            /** @var array<string, mixed> $configuration */
            $configuration = Yaml::parseFile($site . '/config.yaml');
            $writer->write($identifier, $configuration);

            if (is_file($site . '/settings.yaml')) {
                /** @var array<string, mixed> $settings */
                $settings = Yaml::parseFile($site . '/settings.yaml');
                $writer->writeSettings($identifier, $settings);
            }
        }
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
