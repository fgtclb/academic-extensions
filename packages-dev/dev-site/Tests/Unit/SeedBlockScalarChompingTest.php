<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every literal (`|`) and folded (`>`) block of the seed sets states strip
 * chomping (`|-`, `>-`).
 *
 * Without an indicator a block is clipped, and whether it keeps its final
 * newline depended on the symfony/yaml release that read it: 6.4.45, 7.4.18 and
 * earlier dropped it when the block ended a mapping nested in a list item, such
 * as the `self:` of a seed entity, and the next line was less indented. 6.4.47
 * and 7.4.20 keep it everywhere, as the specification says (ACE-776). The
 * functional suites always install the newest release and the development
 * instances their locked one, so the same seed imported different `bodytext`,
 * `pi_flexform` and `constants` in the two, and `SeedManifestTest` failed on
 * every job the day the releases came out. `-` gives the same value in every
 * release, without the final newline.
 *
 * The file is read as text, not parsed: the parser is what cannot be trusted
 * here. The lines inside a block are skipped, so a `|` in a value is not taken
 * for the start of one.
 */
final class SeedBlockScalarChompingTest extends TestCase
{
    /**
     * A line that starts a block scalar: any number of list dashes, an optional
     * key, optional node properties (`!!str`, `&anchor`), the indicator with an
     * optional indentation digit and chomping indicator in either order, and an
     * optional comment.
     */
    private const BLOCK_HEADER = '/^(?<indent> *)(?<dash>(?:- +)*)(?<key>[^\s#-][^#]*?:[ \t]+)?(?:[!&]\S*[ \t]+)*(?<style>[|>])(?<modifiers>[-+1-9]{0,2})[ \t]*(?:#.*)?$/';

    #[Test]
    public function everyBlockScalarOfTheSeedStripsItsFinalNewline(): void
    {
        $directory = dirname(__DIR__, 2) . '/Configuration/DataFactory';
        // Both extensions: the scenario files are ".yaml", the set definitions
        // ".yml", and the file references of a set carry values of their own.
        $files = array_merge(glob($directory . '/*/*.yaml') ?: [], glob($directory . '/*/*.yml') ?: []);
        $this->assertNotSame([], $files, 'No seed set found.');

        $offending = [];
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            $this->assertIsArray($lines);
            foreach (self::findUnstrippedBlocks($lines) as $lineNumber => $line) {
                $offending[] = sprintf('%s/%s:%d: %s', basename(dirname($file)), basename($file), $lineNumber, trim($line));
            }
        }

        $this->assertSame(
            [],
            $offending,
            'Write these blocks with strip chomping ("|-" or ">-"). A clipped block keeps or loses its final'
            . ' newline depending on the symfony/yaml release, see docs/testing/seed-verification.md. A'
            . ' generated file is fixed in its source or its generator, never by hand.',
        );
    }

    /**
     * @return \Generator<string, array{list<string>, list<int>}>
     */
    public static function blockHeaders(): \Generator
    {
        yield 'stripped blocks of every style' => [
            ['a: |-', '  x', 'b: >-', '  y', 'c: |2-', '   z', 'd: |-  # comment', '  w'],
            [],
        ];
        yield 'a clipped and a kept block' => [['a: |', '  x', 'b: |+', '  y'], [1, 3]];
        yield 'a folded block' => [['a: >', '  x'], [1]];
        yield 'a block on a list item' => [['- |', '  x'], [1]];
        yield 'a block on a nested list item' => [['- - |', '    x'], [1]];
        yield 'a sibling after a block on a compact list item' => [['- a: |-', '    x', '  b: |', '    y'], [3]];
        yield 'node properties' => [['a: !!str |', '  x', 'b: &anchor |', '  y'], [1, 3]];
        yield 'a tab before the comment' => [["a: |\t# comment", '  x'], [1]];
        yield 'a line inside a block that looks like a header' => [['a: |-', '  b: |', '  c'], []];
        yield 'a quoted value that looks like a header' => [["a: 'b: |'", 'c: "d: |"'], []];
    }

    /**
     * @param list<string> $lines
     * @param list<int> $expectedLineNumbers
     */
    #[Test]
    #[DataProvider('blockHeaders')]
    public function theGuardFindsEveryClippedOrKeptBlock(array $lines, array $expectedLineNumbers): void
    {
        $this->assertSame($expectedLineNumbers, array_keys(self::findUnstrippedBlocks($lines)));
    }

    /**
     * The lines of a block are those after its header that are empty or indented
     * deeper than the header's key - or, without a key, deeper than the header.
     * A key after a list dash sits at the column after the dash, which is where
     * the siblings of that key start, so they are read as headers of their own.
     *
     * @param list<string> $lines
     * @return array<int, string> the header lines without strip chomping, by line number
     */
    private static function findUnstrippedBlocks(array $lines): array
    {
        $found = [];
        $blockIndent = null;
        foreach ($lines as $index => $line) {
            $indent = strlen($line) - strlen(ltrim($line, ' '));
            if ($blockIndent !== null) {
                if (trim($line) === '' || $indent > $blockIndent) {
                    continue;
                }
                $blockIndent = null;
            }
            if (preg_match(self::BLOCK_HEADER, $line, $match) !== 1) {
                continue;
            }
            $blockIndent = strlen($match['indent']) + (($match['key'] ?? '') !== '' ? strlen($match['dash']) : 0);
            if (!str_contains($match['modifiers'], '-')) {
                $found[$index + 1] = $line;
            }
        }
        return $found;
    }
}
