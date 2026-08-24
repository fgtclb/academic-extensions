<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Checks how every FlexForm shipped by the extensions of this repository declares
 * the `items` of a select or check field.
 *
 * TYPO3 accepts two spellings and deprecates one of them. The positional form
 *
 *     <numIndex index="0" type="array">
 *         <numIndex index="0">LLL:EXT:…:flexform.el.sortByDirection.items.asc</numIndex>
 *         <numIndex index="1">asc</numIndex>
 *     </numIndex>
 *
 * is migrated on the fly by `FlexFormTools` and reported as
 * "uses the legacy way of defining 'items'"; the associative form
 *
 *     <numIndex index="0" type="array">
 *         <label>LLL:EXT:…:flexform.el.sortByDirection.items.asc</label>
 *         <value>asc</value>
 *     </numIndex>
 *
 * is not. Both render the same select box, which is why six files kept the old
 * one through several releases: the migration is silent unless something writes
 * such a FlexForm while `failOnDeprecation` is on (ACE-466).
 *
 * The check is structural and runs without a core: it reads the XML and looks at
 * the shape, rather than parsing the data structure through `FlexFormTools` and
 * waiting for a deprecation. A deprecation is a moving target - it is removed in
 * the version that removes the migration - and by then the wrong spelling stops
 * working rather than warning.
 *
 * `valuePicker` items are deliberately not covered. They are a different
 * structure with its own positional pairs, TYPO3 does not migrate them, and four
 * FlexForms of `academic_persons` rely on them.
 */
final class ShippedFlexFormsTest extends UnitTestCase
{
    /**
     * Directory names that are never a source: generated, installed or vendored trees.
     *
     * @var list<string>
     */
    private const SKIPPED_DIRECTORIES = [
        '.Build',
        '.git',
        'Documentation-GENERATED-temp',
        'node_modules',
        'public',
        'var',
        'vendor',
    ];

    #[Test]
    public function noShippedFlexFormDeclaresItemsInThePositionalForm(): void
    {
        $scanRoot = $this->determineScanRoot();
        $files = $this->collectFlexFormFiles($scanRoot);

        $this->assertNotSame([], $files, sprintf('No FlexForm found below "%s".', $scanRoot));

        $failures = [];
        foreach ($files as $file) {
            foreach ($this->positionalItemLines($file) as $line => $text) {
                $failures[] = sprintf(
                    ' - %s:%d: %s',
                    substr($file, strlen($scanRoot) + 1),
                    $line,
                    trim($text),
                );
            }
        }

        $this->assertSame(
            [],
            $failures,
            sprintf(
                '%d item entries of the %d shipped FlexForms below "%s" use the positional form.'
                . " Write \"<label>\" and \"<value>\" instead:\n%s",
                count($failures),
                count($files),
                $scanRoot,
                implode("\n", $failures),
            ),
        );
    }

    /**
     * The `<TCEforms>` wrapper below `<ROOT>` or an element is a compatibility
     * layer TYPO3 announces as removed in v13: "It should be omitted while the
     * underlying configuration ascends one level up." Two FlexForms of
     * `academic_persons` carried it (ACE-467).
     */
    #[Test]
    public function noShippedFlexFormWrapsItsConfigurationInTceforms(): void
    {
        $scanRoot = $this->determineScanRoot();
        $files = $this->collectFlexFormFiles($scanRoot);

        $failures = [];
        foreach ($files as $file) {
            if (str_contains((string)file_get_contents($file), '<TCEforms>')) {
                $failures[] = ' - ' . substr($file, strlen($scanRoot) + 1);
            }
        }

        $this->assertSame(
            [],
            $failures,
            sprintf(
                '%d of the %d shipped FlexForms below "%s" wrap their configuration in'
                . " \"<TCEforms>\". Omit the tag and move its content one level up:\n%s",
                count($failures),
                count($files),
                $scanRoot,
                implode("\n", $failures),
            ),
        );
    }

    /**
     * The lines of one file that declare an item positionally.
     *
     * A `<numIndex index="N" type="array">` opens an item; inside it, a nested
     * `<numIndex>` is the positional form and a `<label>` or `<value>` is not.
     * Anything below a `<valuePicker>` is skipped.
     *
     * @return array<int, string>
     */
    private function positionalItemLines(string $file): array
    {
        $found = [];
        $valuePickerDepth = 0;
        $inItems = false;
        $inItem = false;

        foreach (explode("\n", (string)file_get_contents($file)) as $index => $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '<valuePicker')) {
                $valuePickerDepth++;
                continue;
            }
            if (str_starts_with($trimmed, '</valuePicker')) {
                $valuePickerDepth--;
                continue;
            }
            if ($valuePickerDepth > 0) {
                continue;
            }
            if ($trimmed === '<items>') {
                $inItems = true;
                continue;
            }
            if ($trimmed === '</items>') {
                $inItems = false;
                $inItem = false;
                continue;
            }
            // The "type" attribute is optional and both spellings occur, so an item
            // is recognised by where it sits rather than by how it is annotated.
            if ($inItems && !$inItem && preg_match('/^<numIndex index="\d+"( type="array")?>$/', $trimmed) === 1) {
                $inItem = true;
                continue;
            }
            if ($inItem && $trimmed === '</numIndex>') {
                $inItem = false;
                continue;
            }
            if ($inItem && str_starts_with($trimmed, '<numIndex ')) {
                $found[$index + 1] = $line;
            }
        }

        return $found;
    }

    /**
     * The whole "packages/" tree when this extension sits in one, and the extension
     * alone when it has been split out to a repository of its own.
     */
    private function determineScanRoot(): string
    {
        $extensionRoot = dirname(__DIR__, 3);
        $packagesRoot = dirname($extensionRoot, 2);

        if (basename($packagesRoot) === 'packages' && is_dir($packagesRoot)) {
            return $packagesRoot;
        }

        return $extensionRoot;
    }

    /**
     * @return list<string>
     */
    private function collectFlexFormFiles(string $root): array
    {
        $directories = new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS);
        $filtered = new \RecursiveCallbackFilterIterator(
            $directories,
            static function (\SplFileInfo $current): bool {
                if ($current->isDir()) {
                    return !in_array($current->getFilename(), self::SKIPPED_DIRECTORIES, true);
                }

                // Case insensitively, because the directory is not spelled the same
                // everywhere: eleven extensions use "Configuration/FlexForms/" and
                // "academic_jobs" uses "Configuration/Flexforms/". Matching the
                // majority spelling exactly is how this check silently skipped that
                // extension, and with it four data structures that needed the fix
                // (ACE-467).
                return strtolower($current->getExtension()) === 'xml'
                    && str_contains(strtolower($current->getPathname()), '/configuration/flexforms/');
            },
        );

        $files = [];
        foreach (new \RecursiveIteratorIterator($filtered) as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
