<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A cell of a `list-table`, a `t3-field-list-table` or a `csv-table` in a manual holds only the directives the renderer of
 * docs.typo3.org is known to render there, and an icon in a cell is a `figure`.
 *
 * Since render-guides 0.46.0, which brought phpDocumentor/guides 1.11, most directives are
 * turned into their node while a page is compiled instead of while it is parsed, and the
 * compiler does not walk into the rows of a table. In a table cell an `image` is dropped,
 * and a `note`, a `tip` or a `container` loses its box and keeps only its text. Nothing is
 * logged, so `--fail-on-log` does not catch it, and a local render with an older cached
 * `latest` image still shows the cell as written. The icon column of the plugin tables of
 * nine manuals was empty on docs.typo3.org until ACE-935.
 *
 * The directives in {@see self::VERIFIED_DIRECTIVES} are still created while parsing, by
 * the theme of the renderer, and were rendered in a table cell on render-guides 0.45.0 and
 * 0.47.2. Any other directive is refused until it has been rendered in a cell on both a
 * release before 0.46.0 and the current one. A substitution reference such as `|icon|` is
 * refused as well, wherever its definition stands: it is not replaced in a table cell on
 * either release, the cell shows its name. A definition inside a table is refused with its
 * directive. Text in inline literals and interpreted text is not read for references.
 *
 * A grid table is not scanned. The options of a directive in a grid table cell are not
 * parsed on either release, they come out as a field list, which a local render already
 * shows.
 */
final class DocumentationTableCellDirectiveTest extends TestCase
{
    private const TABLE_DIRECTIVES = ['csv-table', 'list-table', 't3-field-list-table'];

    private const VERIFIED_DIRECTIVES = ['code-block', 'figure', 'versionadded'];

    /**
     * @return \Generator<string, array{string}>
     */
    public static function documentationDirectoriesDataProvider(): \Generator
    {
        foreach (self::documentationDirectories() as $directory) {
            yield basename(dirname($directory)) => [$directory];
        }
    }

    #[DataProvider('documentationDirectoriesDataProvider')]
    #[Test]
    public function listTableCellsHoldOnlyVerifiedDirectives(string $directory): void
    {
        $found = [];
        foreach (self::rstFiles($directory) as $file) {
            foreach (self::directivesInListTables((string)file_get_contents($file)) as [$line, $name]) {
                if (!in_array($name, self::VERIFIED_DIRECTIVES, true)) {
                    $found[] = sprintf('%s:%d %s', substr($file, strlen($directory) + 1), $line, $name);
                }
            }
        }

        $this->assertSame(
            [],
            $found,
            'The renderer drops these in a table cell or renders them without their markup. An image is written as a figure.',
        );
    }

    #[Test]
    public function directivesInsideAListTableAreFoundAndNoneOutside(): void
    {
        $content = implode("\n", [
            '..  image:: /Images/before.svg',
            '',
            '..  list-table::',
            '    :header-rows: 1',
            '',
            '    *   -   Icon',
            '        -   Name',
            '    *   -   ..  image:: /Images/cell.svg',
            '                :alt: In a cell',
            '        -   Text',
            '    *   -   ..  figure:: /Images/figure.svg',
            '                :alt: A figure',
            '        -   A paragraph first',
            '',
            '            ..  NOTE::',
            '',
            '..  |icon| image:: /Images/substitution.svg',
            '',
            'Text with |icon|.',
            '',
            '*   ..  t3-field-list-table::',
            '',
            '        *   -   ..  code-block:: php',
            '',
            '..  image:: /Images/after.svg',
            '',
            '..  list-table::',
            '',
            '    *   -   ..  |cell| image:: /Images/substitution-in-table.svg',
            '        -   |icon| and |other|_',
            '    *   -   ``|literal|``, :php:`int|string|null` and a|b|c',
            '        -   ..  my_domain:Some.Directive+x:: argument',
            '',
            '..  csv-table::',
            '    :header: "Kind", "Cell"',
            '',
            '    "image", "..  image:: /Images/csv.svg',
            '        :alt: In a CSV cell"',
            '    "note", "..  note::',
            '',
            '        A note", "|icon|"',
        ]);

        $this->assertSame(
            [
                [8, 'image'],
                [11, 'figure'],
                [15, 'note'],
                [23, 'code-block'],
                [29, 'image'],
                [30, 'substitution'],
                [30, 'substitution'],
                [32, 'my_domain:some.directive+x'],
                [37, 'image'],
                [39, 'note'],
                [41, 'substitution'],
            ],
            self::directivesInListTables($content),
        );
    }

    /**
     * The plugin tables of the integrator chapters carry their icons as figures, so a scan
     * that finds no figure in a table cell reads no table at all.
     */
    #[Test]
    public function theScanReadsTheTablesOfTheManuals(): void
    {
        $names = [];
        foreach (self::documentationDirectories() as $directory) {
            foreach (self::rstFiles($directory) as $file) {
                foreach (self::directivesInListTables((string)file_get_contents($file)) as [, $name]) {
                    $names[] = $name;
                }
            }
        }

        $this->assertContains('figure', $names);
    }

    /**
     * Every directive inside a list table, with its line number and its name in lower case,
     * and every substitution reference there as `substitution`. A list table ends at the
     * first line that is not blank and indented no deeper than the table directive.
     *
     * @return list<array{int, string}>
     */
    private static function directivesInListTables(string $content): array
    {
        $lines = explode("\n", $content);
        $found = [];
        $tableIndent = null;
        foreach ($lines as $index => $line) {
            $indent = strlen($line) - strlen(ltrim($line));
            if ($tableIndent !== null && trim($line) !== '' && $indent <= $tableIndent) {
                $tableIndent = null;
            }
            if ($tableIndent === null) {
                $tableIndent = self::tableDirectiveColumn($line);
                continue;
            }
            $directive = '/\.\.\s+(?:\|[^|]+\|\s+)?([a-z0-9][\w.+-]*(?::[a-z0-9][\w.+-]*)?)::/i';
            if (preg_match($directive, $line, $matches) === 1) {
                $found[] = [$index + 1, strtolower($matches[1])];
                $line = (string)preg_replace($directive, '', $line, 1);
            }
            $text = (string)preg_replace(['/``.+?``/', '/`[^`]+`/'], '', $line);
            $references = preg_match_all('/(?<![\w|])\|[^|\s][^|]*?\|(?:__?)?(?![\w|])/', $text);
            for ($i = 0; $i < $references; $i++) {
                $found[] = [$index + 1, 'substitution'];
            }
        }
        return $found;
    }

    /**
     * The column the explicit markup of a table directive starts in, or null for any other line.
     */
    private static function tableDirectiveColumn(string $line): ?int
    {
        $pattern = '/^(.*?)\.\.\s+(' . implode('|', array_map('preg_quote', self::TABLE_DIRECTIVES)) . ')::/';
        if (preg_match($pattern, $line, $matches) !== 1 || preg_match('/^[\s*\-+]*$/', $matches[1]) !== 1) {
            return null;
        }
        return strlen($matches[1]);
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }

    /**
     * @return list<string>
     */
    private static function documentationDirectories(): array
    {
        $directories = glob(self::repositoryPath() . '/packages/fgtclb/*/Documentation', GLOB_ONLYDIR) ?: [];
        sort($directories);
        return $directories;
    }

    /**
     * @return list<string>
     */
    private static function rstFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'rst') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }
}
