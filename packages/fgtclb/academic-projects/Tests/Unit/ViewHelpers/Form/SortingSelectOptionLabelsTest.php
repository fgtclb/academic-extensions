<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\ViewHelpers\Form;

use FGTCLB\AcademicProjects\Enumeration\SortingOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The sorting select of the project list offers, in its default "combined" type,
 * one option per sorting option, labelled from "sorting.<field>.<direction>" of
 * the frontend language file.
 *
 * The English file lacked the "last updated" and manual sorting options, so every
 * visitor saw a bare key like "lastUpdated.asc" for them. The German file carried
 * its translations below "filter.sorting.", a key nothing reads, so a German
 * visitor saw the English labels for the rest. Its "last updated" labels named the
 * opposite direction as well (ACE-859). The labels are read from the shipped XLIFF
 * files, so the check needs no core and names the file that is wrong.
 */
final class SortingSelectOptionLabelsTest extends UnitTestCase
{
    private const LANGUAGE_PATH = __DIR__ . '/../../../../Resources/Private/Language/';

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function languageFileDataProvider(): \Generator
    {
        yield 'English' => ['locallang.xlf', 'source'];
        yield 'German' => ['de.locallang.xlf', 'target'];
    }

    #[Test]
    #[DataProvider('languageFileDataProvider')]
    public function everyCombinedSortingOptionCarriesALabelOfItsOwn(string $fileName, string $element): void
    {
        $labels = $this->labelsOf($fileName, $element);
        $optionLabels = [];
        foreach (SortingOptions::getConstants() as $value) {
            $key = 'sorting.' . str_replace(' ', '.', $value);
            $this->assertArrayHasKey($key, $labels, sprintf('"%s" has no label "%s".', $fileName, $key));
            $optionLabels[$value] = $labels[$key];
        }

        $this->assertSame(
            [],
            array_keys(array_filter(array_count_values($optionLabels), static fn(int $count): bool => $count > 1)),
            sprintf('Several sorting options of "%s" carry the same label.', $fileName),
        );
    }

    /**
     * "lastUpdated" holds a timestamp, so ascending puts the most recently updated
     * project last.
     */
    #[Test]
    public function lastUpdatedLabelsNameTheDirectionTheyOrderIn(): void
    {
        $english = $this->labelsOf('locallang.xlf', 'source');
        $german = $this->labelsOf('de.locallang.xlf', 'target');

        $this->assertSame('Last updated (latest last)', $english['sorting.lastUpdated.asc'] ?? null);
        $this->assertSame('Last updated (latest first)', $english['sorting.lastUpdated.desc'] ?? null);
        $this->assertSame('Zuletzt aktualisiert (älteste zuerst)', $german['sorting.lastUpdated.asc'] ?? null);
        $this->assertSame('Zuletzt aktualisiert (neueste zuerst)', $german['sorting.lastUpdated.desc'] ?? null);
    }

    /**
     * A unit only the translation declares has no English text, so a site in the
     * default language shows the bare key where a German one shows the label.
     */
    #[Test]
    public function germanFileDeclaresNoUnitTheEnglishFileLacks(): void
    {
        $this->assertSame(
            [],
            array_values(array_diff(
                array_keys($this->labelsOf('de.locallang.xlf', 'target')),
                array_keys($this->labelsOf('locallang.xlf', 'source')),
            )),
        );
    }

    /**
     * @return array<string, string>
     */
    private function labelsOf(string $fileName, string $element): array
    {
        $xml = simplexml_load_file(self::LANGUAGE_PATH . $fileName);
        $this->assertNotFalse($xml, sprintf('"%s" is not well formed.', $fileName));

        $labels = [];
        foreach ($xml->file->body->{'trans-unit'} as $unit) {
            $labels[(string)$unit['id']] = trim((string)$unit->{$element});
        }

        return $labels;
    }
}
