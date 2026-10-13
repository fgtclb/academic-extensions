<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Asserts that the pointer of an inline child to its parent keeps the live parent when
 * the parent is edited in a workspace (ACE-907).
 *
 * The first workspace edit of a record versions its workspace aware inline children in
 * the same command, and `DataHandler` records each new version in its copy mapping, live
 * uid to version uid. At the end it rewrites every relation field of the new versions
 * through that mapping, a `select` with a `foreign_table` and a `group`. A child whose
 * pointer to its parent is such a field then points at the parent's version row, which
 * publishing deletes. The workspace module writes the live parent into the children once
 * more after publishing. Publishing from live, as `workspace:autopublish` does on the
 * publish date of a workspace, does not, and neither does the module when a page and the
 * role of its children were edited in the same workspace. The published child then
 * belongs to no record.
 *
 * Naming the parent table in `dontRemapTablesOnCopy` of the pointer stops that rewrite.
 * The pointer is still written by the inline relation of the parent: the live parent for
 * a version, the copied parent for a copy, the translated parent for a translation. A
 * pointer typed `passthrough`, or a `select` without a `foreign_table`, is no relation to
 * `DataHandler` and needs nothing.
 *
 * The assertion reads the compiled TCA, so a relation that an extension adds to a table
 * of another one, a page for example, is found as well. It does not look into FlexForms,
 * which hold no inline relation in the academic extensions.
 */
trait InlineParentPointerAssertionTrait
{
    /**
     * @param list<string> $childTablePrefixes The child tables whose relations to check.
     * @param list<string> $expectedRelations Every inline relation to those child tables, as
     *                                        `<parent table>.<inline column> -> <child table>.<pointer column>`.
     */
    private function assertInlineParentPointersKeepTheLiveParent(array $childTablePrefixes, array $expectedRelations): void
    {
        $relations = [];
        $failures = [];
        foreach ($GLOBALS['TCA'] as $parentTable => $parentTca) {
            foreach ($parentTca['columns'] ?? [] as $columnName => $columnTca) {
                $config = $columnTca['config'] ?? [];
                $childTable = (string)($config['foreign_table'] ?? '');
                $pointerName = (string)($config['foreign_field'] ?? '');
                if (($config['type'] ?? '') !== 'inline'
                    || $pointerName === ''
                    || !$this->tableHasOneOfThePrefixes($childTable, $childTablePrefixes)
                ) {
                    continue;
                }
                $relations[] = sprintf('%s.%s -> %s.%s', $parentTable, $columnName, $childTable, $pointerName);
                $pointerConfig = $GLOBALS['TCA'][$childTable]['columns'][$pointerName]['config'] ?? [];
                $pointerType = (string)($pointerConfig['type'] ?? '');
                $pointerIsRelation = $pointerType === 'group'
                    || ($pointerType === 'select' && (string)($pointerConfig['foreign_table'] ?? '') !== '');
                if (!$pointerIsRelation) {
                    continue;
                }
                $tablesNotRemapped = GeneralUtility::trimExplode(',', (string)($pointerConfig['dontRemapTablesOnCopy'] ?? ''), true);
                if (!in_array($parentTable, $tablesNotRemapped, true)) {
                    $failures[] = sprintf(
                        '"%s.%s" is a "%s" relation to its parent and does not name "%s" in "dontRemapTablesOnCopy".',
                        $childTable,
                        $pointerName,
                        $pointerType,
                        $parentTable,
                    );
                }
            }
        }

        sort($relations);
        sort($expectedRelations);
        $this->assertSame($expectedRelations, $relations, 'The inline relations of the TCA differ from the ones the test names.');
        $this->assertSame(
            [],
            array_values(array_unique($failures)),
            'A parent edited in a workspace and published would leave these children pointing at its deleted version row.',
        );
    }

    /**
     * @param list<string> $prefixes
     */
    private function tableHasOneOfThePrefixes(string $tableName, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($tableName, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
