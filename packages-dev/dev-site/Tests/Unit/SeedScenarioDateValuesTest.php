<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * The seed models years, not days: every profile information date is the
 * first of January and every dated record is flagged `year_only`, which is
 * exactly the shape the upgrade wizard produces from a 2.x installation.
 * Pinning the literals keeps the manifests independent of the import date
 * and of how a core version normalises a datetime value.
 */
final class SeedScenarioDateValuesTest extends TestCase
{
    private const DATE_KEYS = ['date', 'date_start', 'date_end'];

    #[Test]
    public function profileInformationDatesAreFirstOfJanuaryAndFlaggedYearOnly(): void
    {
        $scenario = Yaml::parseFile(
            dirname(__DIR__, 2) . '/Configuration/DataFactory/academics-instance/Scenario.yaml',
        );
        $this->assertIsArray($scenario);

        $records = $this->collectProfileInformationRecords($scenario);
        $this->assertNotEmpty($records);

        $datedRecords = 0;
        foreach ($records as $record) {
            $dates = array_intersect_key($record, array_flip(self::DATE_KEYS));
            if ($dates === []) {
                continue;
            }
            $datedRecords++;
            foreach ($dates as $key => $value) {
                $this->assertIsString($value, sprintf('%s of record %s is a quoted literal', $key, $record['id'] ?? '?'));
                $this->assertMatchesRegularExpression(
                    '/^\d{4}-01-01$/',
                    $value,
                    sprintf('%s of record %s is a year-only SQL DATE', $key, $record['id'] ?? '?'),
                );
            }
            $this->assertSame(1, $record['year_only'] ?? null, sprintf('record %s is flagged year_only', $record['id'] ?? '?'));
        }
        $this->assertGreaterThan(0, $datedRecords);
    }

    /**
     * Every `self` of a `profileInformation` list, including the language
     * variants, wherever the list sits in the scenario tree.
     *
     * @param array<mixed> $node
     * @return list<array<string, mixed>>
     */
    private function collectProfileInformationRecords(array $node): array
    {
        $records = [];
        foreach ($node as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if ($key === 'profileInformation') {
                foreach ($value as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (is_array($item['self'] ?? null)) {
                        $records[] = $item['self'];
                    }
                    foreach ($item['languageVariants'] ?? [] as $variant) {
                        if (is_array($variant['self'] ?? null)) {
                            $records[] = $variant['self'];
                        }
                    }
                }
                continue;
            }
            $records = [...$records, ...$this->collectProfileInformationRecords($value)];
        }
        return $records;
    }
}
