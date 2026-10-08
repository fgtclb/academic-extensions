<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\Backend\FormEngine;

use FGTCLB\AcademicProjects\Backend\FormEngine\SortingItemsProcFunc;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The sorting select of the project list offers one item per sorting option, and
 * an editor tells them apart by their label alone.
 *
 * Both manual sorting directions carried the same text, and the two "last updated"
 * directions carried each other's (ACE-831). The labels are read from the shipped
 * XLIFF files rather than through the language service, so the check needs no
 * core and names the file and the language that is wrong.
 */
final class SortingItemsProcFuncTest extends UnitTestCase
{
    private const LANGUAGE_PATH = __DIR__ . '/../../../../Resources/Private/Language/';

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function languageFileDataProvider(): \Generator
    {
        yield 'English' => ['locallang_be.xlf', 'source'];
        yield 'German' => ['de.locallang_be.xlf', 'target'];
    }

    #[Test]
    #[DataProvider('languageFileDataProvider')]
    public function everySortingItemCarriesALabelOfItsOwn(string $fileName, string $element): void
    {
        $labels = $this->labelsOf($fileName, $element);
        $itemLabels = [];
        foreach ($this->items() as $item) {
            $key = substr($item['label'], strlen('LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:'));
            $this->assertArrayHasKey($key, $labels, sprintf('"%s" has no label "%s".', $fileName, $key));
            $itemLabels[$item['value']] = $labels[$key];
        }

        $this->assertSame(
            [],
            array_keys(array_filter(array_count_values($itemLabels), static fn(int $count): bool => $count > 1)),
            sprintf('Several sorting items of "%s" carry the same label.', $fileName),
        );
    }

    /**
     * "lastUpdated" holds a timestamp, so ascending puts the most recently updated
     * project last.
     */
    #[Test]
    public function lastUpdatedLabelsNameTheDirectionTheyOrderIn(): void
    {
        $english = $this->labelsOf('locallang_be.xlf', 'source');
        $german = $this->labelsOf('de.locallang_be.xlf', 'target');

        $this->assertSame('Last updated (latest last)', $english['flexform.sorting.lastUpdated.asc'] ?? null);
        $this->assertSame('Last updated (latest first)', $english['flexform.sorting.lastUpdated.desc'] ?? null);
        $this->assertSame('Zuletzt aktualisiert (älteste zuerst)', $german['flexform.sorting.lastUpdated.asc'] ?? null);
        $this->assertSame('Zuletzt aktualisiert (neueste zuerst)', $german['flexform.sorting.lastUpdated.desc'] ?? null);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function items(): array
    {
        $params = ['items' => []];
        (new SortingItemsProcFunc())->itemsProcFunc($params);
        $this->assertNotSame([], $params['items']);

        return $params['items'];
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
