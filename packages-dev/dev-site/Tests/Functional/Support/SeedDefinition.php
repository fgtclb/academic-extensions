<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional\Support;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * What the seed set "academics-instance" says about itself, read from its YAML
 * without a database.
 *
 * Two questions are answered here, and both are what makes the manifest of this
 * package checkable against something that is not the run that wrote it:
 *
 * - **which uid a table's rows have**, so a check can look at the rows the seed
 *   wrote and ignore the ones an installation has anyway - the admin backend
 *   user of a development instance is uid 1 in one context and a test fixture
 *   in the other, and comparing it would compare two things that were never
 *   meant to agree;
 * - **which columns the seed has an opinion about**, so the comparison covers
 *   what the definition states and nothing else. A column outside that set is
 *   by definition not something the seed says anything about, and it is also
 *   where the two contexts legitimately differ: a development instance has
 *   EXT:bootstrap_package installed and its columns on "pages", a functional
 *   test instance does not.
 *
 * Derived rather than listed: a column added to the seed enters the projection
 * by itself, the checksum changes, and the manifest has to be regenerated. A
 * hand written list would have to be remembered instead.
 */
final class SeedDefinition
{
    /**
     * The set, addressed as an extension path so it resolves the same way
     * `SeedDefinitionParser::parseFile()` resolves it - that method goes through
     * `GeneralUtility::getFileAbsFileName()`, which needs the package to be
     * active rather than merely present on disk.
     */
    public const SET_DIRECTORY = 'EXT:academics_dev_site/Configuration/DataFactory/academics-instance';

    public const CONFIG_FILE = self::SET_DIRECTORY . '/config.yml';

    /**
     * The generated variant, imported by every instance that has site sets.
     */
    public const SETS_SET_DIRECTORY = self::SET_DIRECTORY . '-sets';

    public const SETS_CONFIG_FILE = self::SETS_SET_DIRECTORY . '/config.yml';

    /**
     * The set the instance of the running core version is built from.
     *
     * TYPO3 v12 has no site sets, so `core-12` imports the set carrying the root
     * `sys_template` record and one tree. From v13 on the instance imports the
     * generated `-sets` variant: the same tree delivered through site sets, plus
     * the `/legacy/` mirror that keeps the older mechanism covered. What the seed
     * says is identical either way; how it is delivered is not.
     */
    public static function configFile(): string
    {
        return (new Typo3Version())->getMajorVersion() >= 13
            ? self::SETS_CONFIG_FILE
            : self::CONFIG_FILE;
    }

    /**
     * Columns that are dropped from every projection although the seed declares
     * them.
     *
     * `password` is written in plain text and hashed by `DataHandler` on save,
     * with a salt drawn per run - two correct imports of the same seed hold two
     * different strings, and comparing them would compare the random number
     * generator.
     */
    private const VOLATILE_COLUMNS = ['password'];

    /**
     * Not a column of `sys_file_reference` but what replaces `uid_local` in its
     * projection: the `identifier` of the file the reference points at. The uid
     * itself says nothing that survives a second import; the path does.
     */
    public const REFERENCED_FILE = '@file';

    /**
     * The projection of the two FAL tables, which no scenario declares: files
     * and file references are declared in `config.yml` and written by the file
     * seeder, so there is nothing to derive them from.
     *
     * `uid` is absent from both. A `sys_file` uid comes from the FAL indexer
     * and a `sys_file_reference` uid from an insert order that spans two
     * passes - neither is declared anywhere, so neither is something the seed
     * states. `uid_local` is absent for the same reason and is replaced by the
     * identifier of the file it points at, which is what a reader actually
     * wants to know: *which* file hangs on the record.
     *
     * @var array<string, list<string>>
     */
    private const FAL_COLUMNS = [
        'sys_file' => ['storage', 'identifier', 'name', 'extension', 'mime_type', 'type', 'size', 'sha1', 'missing'],
        'sys_file_reference' => [
            self::REFERENCED_FILE,
            'pid',
            'tablenames',
            'fieldname',
            'uid_foreign',
            'sys_language_uid',
            'sorting_foreign',
            'title',
            'alternative',
        ],
    ];

    /** @var array<string, mixed>|null */
    private ?array $config = null;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $entitySettings = null;

    /** @var array<string, list<int>>|null */
    private ?array $uids = null;

    /** @var array<string, list<string>>|null */
    private ?array $columns = null;

    /**
     * The declared uids of every table a scenario of the set writes, sorted.
     *
     * @return array<string, list<int>>
     */
    public function declaredUids(): array
    {
        $this->read();

        return $this->uids ?? [];
    }

    /**
     * The columns the set states a value for, per table, sorted - plus the two
     * FAL tables, whose projection is fixed.
     *
     * @return array<string, list<string>>
     */
    public function projection(): array
    {
        $this->read();

        return $this->columns ?? [];
    }

    /**
     * Every table the set writes, in a stable order.
     *
     * @return list<string>
     */
    public function tables(): array
    {
        return array_keys($this->projection());
    }

    /**
     * The file identifiers of the set, in declared order.
     *
     * @return list<string>
     */
    public function fileIdentifiers(): array
    {
        $identifiers = [];
        foreach ($this->config()['files'] ?? [] as $file) {
            $identifiers[] = (string)$file['identifier'];
        }

        return $identifiers;
    }

    /**
     * The declared file references, as `<table>:<uid>:<field>` - the shape the
     * mirror check of the legacy tree compares.
     *
     * @return list<string>
     */
    public function declaredReferences(): array
    {
        $references = [];
        foreach ($this->config()['references'] ?? [] as $reference) {
            $references[] = sprintf(
                '%s:%d:%s',
                (string)$reference['table'],
                (int)$reference['uid'],
                (string)$reference['field'],
            );
        }

        return $references;
    }

    /**
     * A table whose rows are addressed by the uids the set declares, as opposed
     * to one that is read whole.
     *
     * The two FAL tables are read whole on purpose: a translated page and a
     * translated profile inherit their file relation, so `sys_file_reference`
     * holds rows the set never declared - and those are exactly the rows a
     * manifest counted from the YAML would miss.
     */
    public function isUidAddressed(string $table): bool
    {
        return !isset(self::FAL_COLUMNS[$table]);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        if ($this->config === null) {
            $this->config = $this->parse(self::configFile());
        }

        return $this->config;
    }

    private function read(): void
    {
        if ($this->columns !== null) {
            return;
        }

        $config = $this->config();
        $settings = [];
        $scenarios = [];
        foreach ($config['scenarios'] ?? [] as $scenario) {
            // A scenario is either a path relative to the set or an "EXT:" path:
            // the generated set names its files absolutely, because it is written
            // from a script that has no set directory to be relative to.
            $parsed = $this->parse(
                str_starts_with($scenario, 'EXT:')
                    ? $scenario
                    : dirname(self::configFile()) . '/' . $scenario,
            );
            // The same merge `ScenarioComposer` does before it builds anything,
            // which is why "ScenarioLegacy.yaml" may declare a single entity and
            // inherit the other forty.
            ArrayUtility::mergeRecursiveWithOverrule($settings, $parsed['entitySettings'] ?? []);
            $scenarios[] = $parsed;
        }
        $this->entitySettings = $settings;

        $uids = [];
        $columns = [];
        foreach ($scenarios as $scenario) {
            foreach ($scenario['entities'] ?? [] as $entity => $items) {
                $this->walk((string)$entity, $items, $uids, $columns);
            }
        }

        foreach (self::FAL_COLUMNS as $table => $falColumns) {
            $columns[$table] = $falColumns;
        }

        foreach ($uids as $table => $tableUids) {
            sort($tableUids);
            $uids[$table] = array_values($tableUids);
        }
        foreach ($columns as $table => $tableColumns) {
            $tableColumns = array_values(array_diff(array_unique($tableColumns), self::VOLATILE_COLUMNS));
            sort($tableColumns);
            $columns[$table] = $tableColumns;
        }
        ksort($uids);
        ksort($columns);

        $this->uids = $uids;
        $this->columns = $columns;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, list<int>> $uids
     * @param array<string, list<string>> $columns
     */
    private function walk(string $entity, array $items, array &$uids, array &$columns): void
    {
        $settings = $this->settingsOf($entity);
        $table = (string)$settings['tableName'];
        $columnNames = $settings['columnNames'] ?? [];
        $idColumn = (string)array_search('uid', $columnNames, true);

        foreach ($items as $item) {
            foreach ([$item, ...($item['languageVariants'] ?? [])] as $record) {
                $self = $record['self'] ?? [];
                if (!isset($self[$idColumn])) {
                    throw new \RuntimeException(
                        sprintf(
                            'The entity "%s" has a record without a declared "%s". The seed declares a uid for'
                            . ' every record it writes - one without is written under a counter value and cannot'
                            . ' be addressed by a manifest.',
                            $entity,
                            $idColumn,
                        ),
                        1787300001,
                    );
                }
                $uids[$table][] = (int)$self[$idColumn];
                foreach (array_keys($self) as $key) {
                    $columns[$table][] = (string)($columnNames[$key] ?? $key);
                }
                foreach (array_keys($settings['defaultValues'] ?? []) as $key) {
                    $columns[$table][] = (string)($columnNames[$key] ?? $key);
                }
                foreach ((array)($settings['languageColumnNames'] ?? []) as $languageColumn) {
                    $columns[$table][] = (string)$languageColumn;
                }
                foreach (['nodeColumnName', 'parentColumnName'] as $key) {
                    if (isset($settings[$key])) {
                        $columns[$table][] = (string)$settings[$key];
                    }
                }
                $columns[$table][] = 'uid';

                foreach ($record['entities'] ?? [] as $child => $childItems) {
                    $this->walk((string)$child, $childItems, $uids, $columns);
                }
                foreach ($record['children'] ?? [] as $child) {
                    $this->walk($entity, [$child], $uids, $columns);
                }
            }
        }
    }

    /**
     * The settings of one entity, with the wildcard entry merged in the way the
     * scenario factory merges it.
     *
     * @return array<string, mixed>
     */
    private function settingsOf(string $entity): array
    {
        $settings = $this->entitySettings ?? [];
        if (!isset($settings[$entity])) {
            throw new \RuntimeException(
                sprintf('The scenario declares records of the entity "%s" and no settings for it.', $entity),
                1787300002,
            );
        }

        return array_replace_recursive($settings['*'] ?? [], $settings[$entity]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(string $path): array
    {
        $absolute = GeneralUtility::getFileAbsFileName($path);
        if ($absolute === '' || !is_file($absolute)) {
            throw new \RuntimeException(
                sprintf(
                    'The seed file "%s" was not found. "packages-dev/dev-site" has to be loaded as a test'
                    . ' extension for an "EXT:academics_dev_site/..." path to resolve.',
                    $path,
                ),
                1787300003,
            );
        }

        /** @var array<string, mixed> $parsed */
        $parsed = Yaml::parseFile($absolute);

        return $parsed;
    }
}
