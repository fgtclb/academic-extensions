<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every literal (`|`) and folded (`>`) block of the seed sets states strip
 * chomping (`|-`, `>-`).
 *
 * Without an indicator a block is clipped, and whether it keeps its final
 * newline depended on the symfony/yaml release that read it: 6.4.45 and 7.4.18
 * dropped it when the next line was a less indented key or list item, 6.4.47
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
     * A key or list item whose value is a block scalar: the indicator, an
     * optional indentation digit and chomping indicator in either order, and an
     * optional comment.
     */
    private const BLOCK_HEADER = '/^(?<indent> *)(?:- +)?(?:[^\s#][^#]*?: +)?(?<style>[|>])(?<modifiers>[-+1-9]{0,2}) *(?:#.*)?$/';

    #[Test]
    public function everyBlockScalarOfTheSeedStripsItsFinalNewline(): void
    {
        $files = glob(dirname(__DIR__, 2) . '/Configuration/DataFactory/*/*.yaml') ?: [];
        $this->assertNotSame([], $files, 'No seed set found.');

        $offending = [];
        foreach ($files as $file) {
            foreach ($this->findUnstrippedBlocks($file) as $lineNumber => $line) {
                $offending[] = sprintf('%s:%d: %s', basename($file), $lineNumber, trim($line));
            }
        }

        $this->assertSame(
            [],
            $offending,
            'Write these blocks with strip chomping ("|-" or ">-"). A clipped block keeps or loses its final'
            . ' newline depending on the symfony/yaml release, see docs/testing/seed-verification.md.',
        );
    }

    /**
     * @return array<int, string> the header lines without strip chomping, by line number
     */
    private function findUnstrippedBlocks(string $file): array
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
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
            $blockIndent = strlen($match['indent']);
            if (!str_contains($match['modifiers'], '-')) {
                $found[$index + 1] = $line;
            }
        }
        return $found;
    }
}
