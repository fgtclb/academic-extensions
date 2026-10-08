<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\DataFactory\Seeding\DataHandling\ScenarioSeedResult;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Every translation the seed writes points at its own original.
 *
 * The header of `Scenario.yaml` makes that a rule of the numbering: a German
 * variant carries the uid of its original plus 500, in every table. The
 * manifest cannot see a violation of it. It is generated from an import, so an
 * import that writes the wrong translation parent writes it into the manifest
 * as well, and the snapshot of the instance agrees with both.
 *
 * That is how the partnerships and page contacts of the TYPO3 v12 instance
 * pointed at one unrelated uid for months (ACE-834): their tables declared no
 * language pointer column in their TCA, TYPO3 v12 adds one of the type
 * "passthrough", and a "NEW" placeholder in such a column is written with the
 * value the remap stack computed for the entry before it. The translations of
 * the partner, program and project pages were then deleted and localized again
 * under new uids, which the last of the three checks covers.
 *
 * Stating the pointers as plain uids in the seed is no way around it: a parent
 * that is not written yet is not found by the localization data map processor,
 * and every "l10n_mode: exclude" column of the translation - the role of a
 * partnership or a contact - is stored empty. The two tables declare the
 * columns since ACE-854, as every other translatable table of the extensions
 * does.
 */
final class SeedTranslationParentTest extends AbstractSeedTestCase
{
    /**
     * One import for the three checks: an import of the whole seed takes some
     * twenty seconds, and the three read the same result.
     */
    #[Test]
    public function theImportWritesEveryTranslationFaithfully(): void
    {
        $result = $this->importSeed();

        $this->assertEveryTranslationPointsAtItsOriginal($result);
        $this->assertEveryTranslationCarriesTheExcludedValuesOfItsOriginal($result);
        $this->assertTheImportDeletedNothing($result);
    }

    private function assertEveryTranslationPointsAtItsOriginal(ScenarioSeedResult $result): void
    {

        $mismatches = [];
        $checked = 0;
        foreach (array_keys($result->recordCounts) as $table) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '';
            $parentField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? '';
            if ($languageField === '' || $parentField === '') {
                continue;
            }
            $sourceField = $GLOBALS['TCA'][$table]['ctrl']['translationSource'] ?? '';
            foreach ($this->translatedRows($table, $languageField, $parentField, $sourceField) as $row) {
                $checked++;
                $original = (int)$row['uid'] - 500;
                $actual = [(int)$row[$parentField], $sourceField !== '' ? (int)$row[$sourceField] : $original];
                if ($actual !== [$original, $original]) {
                    $mismatches[] = sprintf(
                        '%s:%d points at %s %d%s, its original is %d',
                        $table,
                        (int)$row['uid'],
                        $parentField,
                        $actual[0],
                        $sourceField !== '' ? sprintf(' and %s %d', $sourceField, $actual[1]) : '',
                        $original,
                    );
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'The import wrote no translation at all.');
        $this->assertSame([], $mismatches, 'Translations that do not point at their original.');
    }

    /**
     * A column with "l10n_mode: exclude" belongs to the original, and DataHandler
     * copies it into the translation. The frontend overlay of TYPO3 v12 reads it
     * from the translation all the same, so a translation without it - a
     * partnership without its role - renders as a partnership without a role.
     */
    private function assertEveryTranslationCarriesTheExcludedValuesOfItsOriginal(ScenarioSeedResult $result): void
    {

        $mismatches = [];
        $checked = 0;
        foreach (array_keys($result->recordCounts) as $table) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '';
            $parentField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? '';
            if ($languageField === '' || $parentField === '') {
                continue;
            }
            $columns = [];
            foreach ($GLOBALS['TCA'][$table]['columns'] ?? [] as $column => $configuration) {
                $type = $configuration['config']['type'] ?? '';
                if (($configuration['l10n_mode'] ?? '') === 'exclude' && !in_array($type, ['inline', 'file'], true)) {
                    $columns[] = $column;
                }
            }
            if ($columns === []) {
                continue;
            }
            $rows = [];
            foreach ($this->rowsOf($table, array_merge(['uid', $languageField], $columns)) as $row) {
                $rows[(int)$row['uid']] = $row;
            }
            foreach ($rows as $uid => $row) {
                if ((int)$row[$languageField] <= 0 || !isset($rows[$uid - 500])) {
                    continue;
                }
                foreach ($columns as $column) {
                    $checked++;
                    if ($this->normalized($row[$column]) !== $this->normalized($rows[$uid - 500][$column])) {
                        $mismatches[] = sprintf(
                            '%s:%d has %s "%s", its original %d has "%s"',
                            $table,
                            $uid,
                            $column,
                            (string)$row[$column],
                            $uid - 500,
                            (string)$rows[$uid - 500][$column],
                        );
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'The import wrote no translation with an excluded column at all.');
        $this->assertSame([], $mismatches, 'Translations without the excluded values of their original.');
    }

    private function assertTheImportDeletedNothing(ScenarioSeedResult $result): void
    {

        $deleted = [];
        foreach (array_keys($result->recordCounts) as $table) {
            $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? '';
            if ($deleteField === '') {
                continue;
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $uids = $queryBuilder
                ->select('uid')
                ->from($table)
                ->where($queryBuilder->expr()->eq($deleteField, $queryBuilder->createNamedParameter(1)))
                ->orderBy('uid')
                ->executeQuery()
                ->fetchFirstColumn();
            foreach ($uids as $uid) {
                $deleted[] = $table . ':' . $uid;
            }
        }

        $this->assertSame([], $deleted, 'Records the import deleted.');
    }

    /**
     * "fe_group" is the one excluded column DataHandler writes as "" into a
     * translation where the original holds "0" - both are "no group".
     */
    private function normalized(mixed $value): string
    {
        $value = (string)$value;

        return $value === '0' ? '' : $value;
    }

    /**
     * @param list<string> $columns
     * @return list<array<string, mixed>>
     */
    private function rowsOf(string $table, array $columns): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select(...$columns)
            ->from($table)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function translatedRows(string $table, string $languageField, string $parentField, string $sourceField): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $columns = array_values(array_filter(['uid', $parentField, $sourceField]));

        return $queryBuilder
            ->select(...$columns)
            ->from($table)
            ->where($queryBuilder->expr()->gt($languageField, $queryBuilder->createNamedParameter(0)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
