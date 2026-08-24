<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use FGTCLB\AcademicsDevSite\Tests\Functional\Support\ConnectionRowReader;
use FGTCLB\AcademicsDevSite\Tests\Functional\Support\SeedDefinition;
use FGTCLB\AcademicsDevSite\Tests\Functional\Support\SeedManifest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * The committed manifest is what one import of the seed set produces.
 *
 * This is the half of the manifest that guards the *definition*: change a page
 * title, add a content element, drop a translation, and the checksum of the
 * table it happened in moves. The other half, `SnapshotManifestTest`, guards the
 * committed database snapshots against the same measurement - together they say
 * that the definition, the artifact and the record of both agree.
 *
 * The measurement is deliberately not a full table comparison. It covers the
 * rows the seed declares and the columns the seed states a value for; a column
 * outside that is not something the definition says anything about, and it is
 * where two correct installations legitimately differ - the development
 * instances have EXT:bootstrap_package and its columns on "pages", a functional
 * test instance does not.
 */
final class SeedManifestTest extends AbstractSeedTestCase
{
    #[Test]
    public function importMatchesTheCommittedManifest(): void
    {
        $this->importSeed();

        $definition = new SeedDefinition();
        $reader = new ConnectionRowReader();
        $expected = SeedManifest::load();

        $actual = $expected->measure($reader, $definition);
        $differences = $expected->differencesTo($actual);

        // The column lists too, and against what the definition produces today
        // rather than against the measurement: the measurement was taken with
        // the manifest's own columns, so a column the seed has gained since
        // would not show up in it.
        $derived = SeedManifest::existingProjection($reader, $definition);
        foreach ($expected->tables as $table => $stored) {
            if (($derived[$table] ?? []) !== $stored['columns']) {
                $differences[] = sprintf(
                    '%s: the seed states columns the manifest does not, or the other way round (%s)',
                    $table,
                    implode(', ', array_merge(
                        array_diff($stored['columns'], $derived[$table] ?? []),
                        array_diff($derived[$table] ?? [], $stored['columns']),
                    )),
                );
            }
        }

        $this->assertSame(
            [],
            $differences,
            "The import does not match the committed manifest:\n  " . implode("\n  ", $differences)
            . "\n\nEither the change to the seed was not intended, or the manifest was not regenerated -"
            . ' see ' . SeedManifest::class . ' for the command.',
        );
    }

    /**
     * The manifest has to know about every table the import wrote.
     *
     * Asserted separately from the checksums because it fails differently: a
     * table the manifest does not list is a table nothing is checking, and a
     * checksum comparison over a list that is too short reports nothing at all.
     */
    #[Test]
    public function theManifestCoversEveryTableTheImportWrote(): void
    {
        $result = $this->importSeed();

        $written = array_keys($result->recordCounts);
        $written[] = 'sys_file';
        $written[] = 'sys_file_reference';
        sort($written);

        $covered = array_keys(SeedManifest::load()->tables);
        sort($covered);

        $this->assertSame($covered, $written);
    }

    /**
     * Writes the manifest from a real import.
     *
     * Grouped, and `runTests.sh` excludes that group from the `functional`
     * suite, so a normal run checks the manifest and never rewrites it - a
     * check that repairs what it is checking is not a check. The `seedManifest`
     * suite is the run that has it, and it is a suite of its own because a
     * phpunit group filter on the command line replaces the one in the
     * configuration file rather than adding to it.
     */
    #[Test]
    #[Group('seed-manifest-update')]
    public function writeManifest(): void
    {
        $this->importSeed();

        SeedManifest::generate(new ConnectionRowReader(), new SeedDefinition())->write();

        $this->assertFileExists(SeedManifest::file());
    }
}
