<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Unit\Enumeration;

use FGTCLB\AcademicBiteJobs\Enumeration\ListView;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ListViewTest extends UnitTestCase
{
    /**
     * @return array<string, array{0: string, 1: ListView}>
     */
    public static function storedValues(): array
    {
        return [
            'List' => ['List', ListView::LIST],
            'Card' => ['Card', ListView::CARD],
            'Table' => ['Table', ListView::TABLE],
            'ListView before 2.1' => ['ListView', ListView::LIST],
            'CardView before 2.1' => ['CardView', ListView::CARD],
            'TableView before 2.1' => ['TableView', ListView::TABLE],
            'empty value' => ['', ListView::LIST],
            'only the suffix' => ['View', ListView::LIST],
            'unknown value' => ['Grid', ListView::LIST],
            'unknown value with the suffix' => ['GridView', ListView::LIST],
            'value in lower case' => ['card', ListView::LIST],
        ];
    }

    #[DataProvider('storedValues')]
    #[Test]
    public function fromStoredValueResolvesTheView(string $storedValue, ListView $expected): void
    {
        $this->assertSame($expected, ListView::fromStoredValue($storedValue));
    }
}
