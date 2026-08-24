<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional\Support;

use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * The committed measurement of what the seed set writes.
 *
 * It exists because the seed and the `sqlite-databases/core-NN.sqlite`
 * snapshots the development instances are built from are two artifacts of one
 * statement, and this repository has watched them drift apart in silence twice.
 * The manifest is the third artifact that makes the disagreement loud: a fresh
 * import is measured against it, and so is every committed snapshot.
 *
 * It is generated from a real import and never counted from the YAML. A count
 * from the YAML would be wrong before it was written down - `DataHandler`
 * writes a `sys_file_reference` row for the translation of a page and of a
 * profile that no `references:` entry declares, so the seed produces rows
 * nobody can read off it.
 *
 * Regenerate it with:
 *
 *   Build/Scripts/runTests.sh -t 13 -s seedManifest
 *
 * and once more with `-t 14`, after the `composerUpdate` of that version.
 *
 * There is one file per core version, and that is not symmetry for its own
 * sake: the two cores initialise a column the seed does not name differently.
 * TYPO3 v13 writes the schema default into `pages.geocode_status` (`open`) and
 * `tt_content.imagewidth` (`0`) where v14 leaves both `NULL`, on 218 and 236
 * rows respectively. Folding that away would mean dropping the two columns from
 * the projection - hiding a difference that is real - and the artifact the
 * manifest is checked against, `sqlite-databases/core-NN.sqlite`, is per core
 * version anyway.
 */
final class SeedManifest
{
    /**
     * @param array<string, array{rows: int, columns: list<string>, checksum: string}> $tables
     */
    public function __construct(public readonly array $tables) {}

    public static function file(): string
    {
        return sprintf(
            '%s/Fixtures/SeedManifest-core%d.json',
            dirname(__DIR__),
            (new Typo3Version())->getMajorVersion(),
        );
    }

    public static function load(): self
    {
        $file = self::file();
        if (!is_file($file)) {
            throw new \RuntimeException(
                sprintf('The seed manifest "%s" does not exist. Regenerate it - see %s.', $file, self::class),
                1787300301,
            );
        }

        /** @var array{tables: array<string, array{rows: int, columns: list<string>, checksum: string}>} $decoded */
        $decoded = json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        return new self($decoded['tables']);
    }

    /**
     * Measures every table of the set, deriving the projection from the
     * definition - what a regeneration does.
     *
     * The derived projection is reduced to the columns the table actually has.
     * The wildcard entity of the scenario declares `hidden` for everything, and
     * `be_users` calls that column `disable`; a projection that named it anyway
     * would be a manifest that cannot be checked against anything.
     */
    public static function generate(SeedRowReader $reader, SeedDefinition $definition): self
    {
        $uids = $definition->declaredUids();
        $tables = [];
        foreach (self::existingProjection($reader, $definition) as $table => $columns) {
            $measured = $reader->measure(
                $table,
                $columns,
                $definition->isUidAddressed($table) ? ($uids[$table] ?? []) : null,
            );
            $tables[$table] = [
                'rows' => $measured['rows'],
                'columns' => $columns,
                'checksum' => $measured['checksum'],
            ];
        }

        return new self($tables);
    }

    /**
     * Measures the tables *this* manifest names, with the columns it names.
     *
     * Reading the stored column list rather than the derived one is what makes
     * the check a check: a column the manifest names and the table no longer has
     * raises, instead of quietly dropping out of the checksum on both sides.
     */
    public function measure(SeedRowReader $reader, SeedDefinition $definition): self
    {
        $uids = $definition->declaredUids();
        $tables = [];
        foreach ($this->tables as $table => $expected) {
            $measured = $reader->measure(
                $table,
                $expected['columns'],
                $definition->isUidAddressed($table) ? ($uids[$table] ?? []) : null,
            );
            $tables[$table] = [
                'rows' => $measured['rows'],
                'columns' => $expected['columns'],
                'checksum' => $measured['checksum'],
            ];
        }

        return new self($tables);
    }

    /**
     * The projection the definition produces today, reduced to what the tables
     * have - what the stored column lists are compared against, so that a column
     * added to the seed is reported as a manifest that was not regenerated
     * rather than as nothing at all.
     *
     * @return array<string, list<string>>
     */
    public static function existingProjection(SeedRowReader $reader, SeedDefinition $definition): array
    {
        $projection = [];
        foreach ($definition->projection() as $table => $columns) {
            $present = $reader->columnsOf($table);
            $projection[$table] = array_values(array_filter(
                $columns,
                static fn(string $column): bool => $column === SeedDefinition::REFERENCED_FILE
                    || in_array(strtolower($column), $present, true),
            ));
        }

        return $projection;
    }

    public function write(): void
    {
        $payload = [
            'README' => [
                'This file is generated. See ' . self::class . ' for the command that writes it.',
                'It belongs to one TYPO3 core version - the two initialise a column the seed does',
                'not name differently - and there is a second file next to it for the other one.',
                'It is the measurement of one real import of the seed set "academics-instance":',
                'the number of rows the set wrote per table, the columns the set states a value',
                'for, and a checksum over those columns of those rows.',
                'A row is addressed by the uid the seed declares, except in "sys_file" and',
                '"sys_file_reference", which are read whole because the seed does not declare',
                'every row of them - a translated page and a translated profile inherit their',
                'file relation and produce rows no "references:" entry names.',
            ],
            'tables' => $this->tables,
        ];

        file_put_contents(
            self::file(),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        );
    }

    /**
     * What differs between this manifest and another one, as lines a failure
     * message can carry.
     *
     * Reported rather than asserted one table at a time: a change to the seed
     * moves many tables at once, and a message naming all of them says what
     * happened where a message naming the first one says only that something
     * did.
     *
     * @return list<string>
     */
    public function differencesTo(self $other): array
    {
        $differences = [];
        foreach ($this->tables as $table => $expected) {
            if (!isset($other->tables[$table])) {
                $differences[] = sprintf('%s: missing, the manifest expects %d rows', $table, $expected['rows']);
                continue;
            }
            $actual = $other->tables[$table];
            if ($expected['rows'] !== $actual['rows']) {
                $differences[] = sprintf(
                    '%s: %d rows, the manifest expects %d',
                    $table,
                    $actual['rows'],
                    $expected['rows'],
                );
                continue;
            }
            if ($expected['columns'] !== $actual['columns']) {
                $differences[] = sprintf(
                    '%s: the manifest states %d columns and the seed now states %d (%s)',
                    $table,
                    count($expected['columns']),
                    count($actual['columns']),
                    implode(', ', array_merge(
                        array_diff($expected['columns'], $actual['columns']),
                        array_diff($actual['columns'], $expected['columns']),
                    )),
                );
                continue;
            }
            if ($expected['checksum'] !== $actual['checksum']) {
                $differences[] = sprintf(
                    '%s: %d rows as expected, but their content differs (%s instead of %s)',
                    $table,
                    $actual['rows'],
                    $actual['checksum'],
                    $expected['checksum'],
                );
            }
        }
        foreach ($other->tables as $table => $actual) {
            if (!isset($this->tables[$table])) {
                $differences[] = sprintf('%s: %d rows the manifest does not know about', $table, $actual['rows']);
            }
        }

        return $differences;
    }
}
