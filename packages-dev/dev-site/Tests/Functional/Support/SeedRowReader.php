<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional\Support;

/**
 * Reads the rows a manifest is built from, or checked against.
 *
 * There are two of these because there are two things to check: the database a
 * fresh import wrote, and the committed `sqlite-databases/core-NN.sqlite`
 * snapshot the development instances are seeded from. Both have to be reduced
 * to the same canonical text, or the second check would report a difference the
 * first one caused.
 */
abstract class SeedRowReader
{
    /**
     * The columns the table actually has, lower cased.
     *
     * Lower cased because the check has to hold on PostgreSQL, which folds an
     * unquoted identifier - a `sys_file_reference` written as `CType` comes back
     * as `ctype` and would look like a missing column.
     *
     * @return list<string>
     */
    abstract public function columnsOf(string $table): array;

    /**
     * @param list<string> $columns
     * @param list<int>|null $uids The uids to read, or null for the whole table.
     * @return list<array<string, mixed>>
     */
    abstract public function rows(string $table, array $columns, ?array $uids): array;

    /**
     * The `identifier` of every `sys_file` row, keyed by uid - what the
     * synthetic `@file` column of a file reference is resolved through.
     *
     * @return array<int, string>
     */
    abstract public function fileIdentifiers(): array;

    /**
     * One table, reduced to a row count and a checksum over its canonical text.
     *
     * The rows are rendered, then sorted as text, then hashed. Sorting the text
     * rather than ordering the query is what makes the two readers comparable:
     * `ORDER BY` is the database's collation, and two databases do not have to
     * share one.
     *
     * @param list<string> $columns
     * @param list<int>|null $uids
     * @return array{rows: int, checksum: string}
     */
    final public function measure(string $table, array $columns, ?array $uids): array
    {
        $present = $this->columnsOf($table);
        $missing = [];
        foreach ($columns as $column) {
            if ($column === SeedDefinition::REFERENCED_FILE) {
                continue;
            }
            if (!in_array(strtolower($column), $present, true)) {
                $missing[] = $column;
            }
        }
        if ($missing !== []) {
            throw new \RuntimeException(
                sprintf(
                    'The table "%s" has no column %s. The manifest names it, so either the seed no longer'
                    . ' writes it or the schema lost it.',
                    $table,
                    implode(', ', array_map(static fn(string $c): string => '"' . $c . '"', $missing)),
                ),
                1787300101,
            );
        }

        $files = $table === 'sys_file_reference' ? $this->fileIdentifiers() : [];
        $lines = [];
        foreach ($this->rows($table, $columns, $uids) as $row) {
            $rendered = [];
            foreach ($columns as $column) {
                $value = $column === SeedDefinition::REFERENCED_FILE
                    ? ($files[(int)($row['uid_local'] ?? 0)] ?? '<unknown file>')
                    : ($row[$column] ?? null);
                $rendered[] = $column . '=' . $this->canonicalValue($value);
            }
            $lines[] = implode("\x1f", $rendered);
        }
        sort($lines);

        return ['rows' => count($lines), 'checksum' => sha1(implode("\x1e", $lines))];
    }

    /**
     * One value, as text that means the same thing on every database.
     *
     * Two normalisations, both for differences between drivers rather than
     * between seeds:
     *
     * - **`null` and the empty string are folded together.** They are not the
     *   same value, but a column declared `NOT NULL DEFAULT ''` comes back as
     *   one on some database systems and as the other on others - see ACE-358.
     * - **A decimal keeps no trailing zeros.** `pages.tx_academicprojects_budget`
     *   is `decimal(11,2)`; SQLite answers `120000` and PostgreSQL answers the
     *   string `120000.00`. Same number, two drivers.
     */
    private function canonicalValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_float($value)) {
            // Rendered with more decimals than any column of this seed carries,
            // and then trimmed by the rule below like any other decimal.
            $value = sprintf('%.6F', $value);
        }

        $text = str_replace("\r\n", "\n", (string)$value);
        if (preg_match('#^-?\d+\.\d+$#', $text) === 1) {
            $text = rtrim(rtrim($text, '0'), '.');
        }

        return $text;
    }
}
