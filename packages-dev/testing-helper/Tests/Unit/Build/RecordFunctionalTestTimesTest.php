<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\Tests\Unit\Build;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * "Build/Scripts/recordFunctionalTestTimes.php", which writes the durations
 * "splitFunctionalTests.php" balances the chunks by.
 *
 * The keys have to be the same whatever checkout the logs come from - a CI
 * runner records "/home/runner/work/…" - or the committed file would never
 * match a local run. The script is run rather than included: it ends in
 * `exit(main($argv))`.
 */
final class RecordFunctionalTestTimesTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/record-functional-test-times-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
        parent::tearDown();
    }

    #[Test]
    public function durationsAreKeyedRelativeToTheRepositoryRootAndAddedUpAcrossLogs(): void
    {
        $this->junit('chunk-1.xml', [
            '/home/runner/work/academic-extensions/academic-extensions/packages/fgtclb/academic-persons/Tests/Functional/ATest.php' => 12.3456,
            '/var/www/checkout/packages-dev/dev-site/Tests/Functional/SeedTest.php' => 80.0,
        ]);
        $this->junit('chunk-2.xml', [
            '/elsewhere/packages/fgtclb/academic-persons/Tests/Functional/ATest.php' => 1.0,
        ]);

        [$status] = $this->record('chunk-1.xml', 'chunk-2.xml');

        self::assertSame(0, $status);
        self::assertSame(
            '{' . "\n"
            . '    "packages-dev/dev-site/Tests/Functional/SeedTest.php": 80.0,' . "\n"
            . '    "packages/fgtclb/academic-persons/Tests/Functional/ATest.php": 13.35' . "\n"
            . '}' . "\n",
            file_get_contents($this->directory . '/timings.json'),
        );
    }

    #[Test]
    public function aLogWithoutAnyTestClassFailsInsteadOfWritingAnEmptyFile(): void
    {
        $this->junit('empty.xml', []);

        [$status, $output] = $this->record('empty.xml');

        self::assertSame(1, $status);
        self::assertStringContainsString('No test class durations found', $output);
        self::assertFileDoesNotExist($this->directory . '/timings.json');
    }

    #[Test]
    public function aMissingLogFails(): void
    {
        [$status, $output] = $this->record('missing.xml');

        self::assertSame(1, $status);
        self::assertStringContainsString('Cannot read', $output);
    }

    /**
     * The shape phpunit writes: a suite for the run, holding one per test class.
     *
     * @param array<string, float> $secondsPerFile
     */
    private function junit(string $name, array $secondsPerFile): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<testsuites><testsuite name="Functional tests" tests="1" time="1">';
        foreach ($secondsPerFile as $file => $seconds) {
            $xml .= sprintf('<testsuite name="Fixture" file="%s" tests="1" time="%F"><testcase name="test" time="%F"/></testsuite>', $file, $seconds, $seconds);
        }
        file_put_contents($this->directory . '/' . $name, $xml . '</testsuite></testsuites>');
    }

    /**
     * @return array{int, string}
     */
    private function record(string ...$logs): array
    {
        $arguments = [PHP_BINARY, dirname(__DIR__, 5) . '/Build/Scripts/recordFunctionalTestTimes.php', $this->directory . '/timings.json'];
        foreach ($logs as $log) {
            $arguments[] = $this->directory . '/' . $log;
        }
        $output = [];
        $status = 0;
        exec(implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1', $output, $status);
        return [$status, implode("\n", $output)];
    }
}
