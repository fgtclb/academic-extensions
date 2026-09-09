<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Structural checks over every FlexForm shipped by the extensions of this
 * repository. Nothing else in the suite looks at that XML, which is how each of
 * the four defects below shipped.
 *
 * The checks are textual and run without a core, deliberately. Parsing the data
 * structure through `FlexFormTools` and waiting for a deprecation would tie the
 * guard to a moving target: a deprecation disappears in the version that removes
 * the migration behind it, and from then on the wrong spelling stops working
 * rather than warning.
 *
 * What is checked, and what it cost to learn:
 *
 * * `items` of a select or check field must use the associative `label`/`value`
 *   keys. The positional form is migrated on the fly and reported as "uses the
 *   legacy way of defining 'items'", which is silent unless something writes such
 *   a FlexForm while `failOnDeprecation` is on - six files kept it through several
 *   releases (ACE-466).
 * * `items` of a `valuePicker` are the opposite case and get their own two checks
 *   below.
 * * `<TCEforms>` is a wrapper core removed from the parsed array in v12 (breaking
 *   #97126). Its content is not read at all, so a `sheetTitle` inside one is
 *   simply absent (ACE-565).
 * * Character data between two elements is always a typo: XML permits it and
 *   TYPO3 drops it. `academic_persons` carried a lone `s` after
 *   `</settings.organisationalUnits>` through several releases (ACE-464).
 *
 * Forward-ported from branch `2`, where ACE-466 introduced it. One check differs
 * on purpose - see `everyValuePickerLivesInACoreVersionFolder()`.
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

    /**
     * The core major version from which `valuePicker` items are read as
     * `label`/`value` rather than as a positional pair (core feature #106092).
     */
    private const ASSOCIATIVE_VALUE_PICKER_SINCE = 14;

    #[Test]
    public function noShippedFlexFormDeclaresItemsInThePositionalForm(): void
    {
        $scanRoot = $this->determineScanRoot();
        $files = $this->collectFlexFormFiles($scanRoot);

        $this->assertNotSame([], $files, sprintf('No FlexForm found below "%s".', $scanRoot));

        $failures = [];
        foreach ($files as $file) {
            foreach ($this->itemEntries($file) as $entry) {
                if ($entry['valuePicker'] || !$entry['positional']) {
                    continue;
                }
                $failures[] = sprintf(
                    ' - %s:%d: %s',
                    substr($file, strlen($scanRoot) + 1),
                    $entry['line'],
                    trim($entry['text']),
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
     * `valuePicker` is the one item list TYPO3 never made readable in both
     * directions: v13 reads the positional pair and has no migration for anything
     * else, v14 reads `label`/`value` and migrates the positional pair at the cost
     * of an `E_USER_DEPRECATED`. So a data structure carrying one cannot be shared
     * between the two core versions, and shipping it shared is what made four
     * `academic_persons` plugins unopenable in the v13 backend (ACE-560).
     *
     * This is the one check that differs from the branch `2` original, which skips
     * `valuePicker` entirely. There that was right - the branch has no per-version
     * split and its positional pairs are correct for both v12 and v13. Here the
     * rule exists, so the exclusion becomes an assertion.
     */
    #[Test]
    public function everyValuePickerLivesInACoreVersionFolder(): void
    {
        $scanRoot = $this->determineScanRoot();
        $files = $this->collectFlexFormFiles($scanRoot);

        $this->assertNotSame([], $files, sprintf('No FlexForm found below "%s".', $scanRoot));

        $failures = [];
        foreach ($files as $file) {
            if (!str_contains((string)file_get_contents($file), '<valuePicker')) {
                continue;
            }
            $relativePath = substr($file, strlen($scanRoot) + 1);
            if ($this->coreVersionOfFolder($relativePath) === null) {
                $failures[] = ' - ' . $relativePath;
            }
        }

        $this->assertSame(
            [],
            $failures,
            'A FlexForm carrying a "valuePicker" has to exist once per supported core version,'
            . ' below a "Core13" or "Core14" folder, because the two versions read its items'
            . " differently and neither tolerates the other's form. These are shared:\n"
            . implode("\n", $failures),
        );
    }

    /**
     * The positional pair belongs to the `Core13` variant and `label`/`value` to
     * the `Core14` one. Getting this backwards is not cosmetic: on v13 the wrong
     * form raises "Undefined array key 1" and then a `TypeError`, and on v14 it
     * raises a deprecation this suite fails on.
     */
    #[Test]
    public function valuePickerItemsMatchTheirCoreVersionFolder(): void
    {
        $scanRoot = $this->determineScanRoot();
        $files = $this->collectFlexFormFiles($scanRoot);

        $this->assertNotSame([], $files, sprintf('No FlexForm found below "%s".', $scanRoot));

        $failures = [];
        $checkedItems = 0;
        foreach ($files as $file) {
            $relativePath = substr($file, strlen($scanRoot) + 1);
            $coreVersion = $this->coreVersionOfFolder($relativePath);
            if ($coreVersion === null) {
                continue;
            }
            $wantsAssociative = $coreVersion >= self::ASSOCIATIVE_VALUE_PICKER_SINCE;

            foreach ($this->itemEntries($file) as $entry) {
                if (!$entry['valuePicker']) {
                    continue;
                }
                $checkedItems++;
                $isAssociative = !$entry['positional'];
                if ($isAssociative === $wantsAssociative) {
                    continue;
                }
                $failures[] = sprintf(
                    ' - %s:%d: %s (TYPO3 v%d reads %s)',
                    $relativePath,
                    $entry['line'],
                    trim($entry['text']),
                    $coreVersion,
                    $wantsAssociative ? '"<label>" and "<value>"' : 'a positional "<numIndex>" pair',
                );
            }
        }

        $this->assertSame(
            [],
            $failures,
            "Value picker items that do not match the core version of their folder:\n"
            . implode("\n", $failures),
        );

        // Without this the check is a no-op the day the last split value picker
        // is renamed or removed, which is the failure mode it exists to prevent.
        $this->assertGreaterThan(
            0,
            $checkedItems,
            'No value picker item was checked at all. Either none is shipped below a'
            . ' "Core<NN>" folder any more, or the scanner stopped recognising them.',
        );
    }

    /**
     * The `<TCEforms>` wrapper below `<ROOT>` or an element is a compatibility
     * layer TYPO3 removed from the parsed array in v12 (breaking #97126), so its
     * content is silently dropped rather than deprecated. Branch `2` cleared two
     * FlexForms of `academic_persons` under ACE-467; here the equivalent work was
     * `e33e418c6`, which re-emitted the wrapper into one of the two files it
     * rewrote, and that copy survived until ACE-565.
     */
    #[Test]
    public function noShippedFlexFormWrapsItsConfigurationInTceforms(): void
    {
        $scanRoot = $this->determineScanRoot();
        $files = $this->collectFlexFormFiles($scanRoot);

        $this->assertNotSame([], $files, sprintf('No FlexForm found below "%s".', $scanRoot));

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
     * Character data between two elements, which XML permits and TYPO3 ignores.
     *
     * A FlexForm is a tree of elements, so a stray character between them means
     * exactly one thing: a typo. `academic_persons` carried a lone `s` after
     * `</settings.organisationalUnits>` through several releases (ACE-464) - the
     * file was well formed, the parser dropped the text node, and nothing in this
     * repository looks at XML, so no gate ever saw it.
     *
     * The check is on the parsed tree rather than on the text, because that is
     * what makes "between two elements" a decidable question.
     */
    #[Test]
    public function noShippedFlexFormCarriesStrayCharacterData(): void
    {
        $scanRoot = $this->determineScanRoot();
        $files = $this->collectFlexFormFiles($scanRoot);

        $this->assertNotSame([], $files, sprintf('No FlexForm found below "%s".', $scanRoot));

        $failures = [];
        foreach ($files as $file) {
            $document = new \DOMDocument();
            $document->preserveWhiteSpace = true;
            if (!@$document->loadXML((string)file_get_contents($file))) {
                $failures[] = sprintf(' - %s: not well formed XML', substr($file, strlen($scanRoot) + 1));
                continue;
            }

            foreach (self::strayTextOf($document->documentElement) as $stray) {
                $failures[] = sprintf(
                    ' - %s: "%s" between elements below <%s>',
                    substr($file, strlen($scanRoot) + 1),
                    $stray['text'],
                    $stray['parent'],
                );
            }
        }

        $this->assertSame(
            [],
            $failures,
            sprintf(
                '%d stray text nodes in the %d shipped FlexForms below "%s".'
                . " XML allows them and TYPO3 drops them, so they are always a typo:\n%s",
                count($failures),
                count($files),
                $scanRoot,
                implode("\n", $failures),
            ),
        );
    }

    /**
     * Text nodes of an element that also has element children, which is where
     * character data can only be an accident.
     *
     * @return list<array{parent: string, text: string}>
     */
    private static function strayTextOf(?\DOMElement $element): array
    {
        if ($element === null) {
            return [];
        }

        $stray = [];
        $hasElementChild = false;
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $hasElementChild = true;
            }
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $stray = array_merge($stray, self::strayTextOf($child));
                continue;
            }
            if ($hasElementChild && $child instanceof \DOMText && trim($child->wholeText) !== '') {
                $stray[] = ['parent' => $element->nodeName, 'text' => trim($child->wholeText)];
            }
        }

        return $stray;
    }

    /**
     * Every item declaration of one file, with where it sits and how it is spelled.
     *
     * A `<numIndex index="N" type="array">` opens an item; inside it, a nested
     * `<numIndex>` is the positional form and a `<label>` or `<value>` is not.
     * `<labelChecked>` and `<labelUnchecked>` of a check item are neither, which
     * is why the closing bracket is part of the match.
     *
     * The scan is line based and therefore assumes **one element per line**,
     * which is how every FlexForm in this repository is written. An item folded
     * onto a single line is not recognised - it is skipped rather than reported,
     * so the checks stay free of false positives at the price of a blind spot.
     * Formatting the file normally removes it.
     *
     * @return list<array{line: int, text: string, valuePicker: bool, positional: bool}>
     */
    private function itemEntries(string $file): array
    {
        $entries = [];
        $valuePickerDepth = 0;
        $inItems = false;
        $inItem = false;

        foreach (explode("\n", (string)file_get_contents($file)) as $index => $line) {
            $trimmed = trim($line);
            // A self-closing "<valuePicker/>" opens nothing. Counting it would
            // leave the depth stuck above zero and attribute every later item of
            // the file to a value picker, which reports correct select items as
            // wrong rather than merely missing something.
            if (str_starts_with($trimmed, '<valuePicker') && !str_ends_with($trimmed, '/>')) {
                $valuePickerDepth++;
                continue;
            }
            if (str_starts_with($trimmed, '</valuePicker')) {
                $valuePickerDepth = max(0, $valuePickerDepth - 1);
                continue;
            }
            // As with the items themselves, the "type" attribute is optional.
            if (preg_match('/^<items( type="array")?>$/', $trimmed) === 1) {
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
            if (!$inItem) {
                continue;
            }

            $positional = str_starts_with($trimmed, '<numIndex ');
            $associative = str_starts_with($trimmed, '<label>') || str_starts_with($trimmed, '<value>');
            if (!$positional && !$associative) {
                continue;
            }

            $entries[] = [
                'line' => $index + 1,
                'text' => $line,
                'valuePicker' => $valuePickerDepth > 0,
                'positional' => $positional,
            ];
        }

        return $entries;
    }

    /**
     * The core major version a `Core<NN>` folder in the path stands for, or null
     * when the file is shared between all supported versions.
     */
    private function coreVersionOfFolder(string $relativePath): ?int
    {
        if (preg_match('#(?:^|/)Core(\d+)/#', $relativePath, $matches) !== 1) {
            return null;
        }

        return (int)$matches[1];
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

                // Case insensitively. Every extension on this branch spells the
                // directory "Configuration/FlexForms/", but matching one spelling
                // exactly is how this check once skipped a whole extension, and
                // with it four data structures that needed the fix (ACE-467).
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
