<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SeedScenarioDateValuesTest extends TestCase
{
    #[Test]
    public function profileInformationYearsUseUnambiguousSqlDates(): void
    {
        $scenario = file_get_contents(
            dirname(__DIR__, 2) . '/Configuration/DataFactory/academics-instance/Scenario.yaml',
        );
        $this->assertIsString($scenario);

        preg_match_all(
            '/^\s+year(?:_start|_end)?:\s+(.+)$/m',
            $scenario,
            $matches,
        );
        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $date) {
            $this->assertMatchesRegularExpression(
                "/^'\d{4}-01-01'$/",
                $date,
                'Year-only SQL DATE values must not depend on the import date or core version.',
            );
        }
    }
}
