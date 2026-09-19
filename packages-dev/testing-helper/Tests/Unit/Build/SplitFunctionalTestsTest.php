<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\Tests\Unit\Build;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * "Build/Scripts/splitFunctionalTests.php", which "runTests.sh -j" splits the
 * functional suite with.
 *
 * What matters is that every test class runs exactly once, whatever the split,
 * and that the heavy classes do not pile up in one chunk. The script is run
 * rather than included: it ends in `exit(main($argv))`.
 */
final class SplitFunctionalTestsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/split-functional-tests-' . bin2hex(random_bytes(4));
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
    public function everyTestClassRunsInExactlyOneChunk(): void
    {
        $tests = ['A' => 7, 'B' => 3, 'C' => 3, 'D' => 2, 'E' => 1, 'F' => 1];

        [$status] = $this->split($tests, 3);

        self::assertSame(0, $status);
        $chunks = $this->filesPerChunk(3);
        $all = array_merge(...array_values($chunks));
        sort($all);
        self::assertSame(array_map($this->file(...), array_keys($tests)), $all);
    }

    #[Test]
    public function withoutTimingsTheHeaviestClassGoesFirstIntoTheLightestChunk(): void
    {
        [$status] = $this->split(['A' => 10, 'B' => 6, 'C' => 5, 'D' => 5], 2);

        self::assertSame(0, $status);
        // 10 -> 1, 6 -> 2, 5 -> 2 (6 < 10), 5 -> 1 (10 < 11)
        self::assertSame(
            [1 => [$this->file('A'), $this->file('D')], 2 => [$this->file('B'), $this->file('C')]],
            $this->filesPerChunk(2),
        );
    }

    #[Test]
    public function recordedDurationsOutweighTheNumberOfTests(): void
    {
        // One slow class with a single test, two fast ones with many.
        $tests = ['Slow' => 1, 'FastOne' => 50, 'FastTwo' => 50];
        $timings = ['Slow' => 100.0, 'FastOne' => 10.0, 'FastTwo' => 10.0];

        [$status, $output] = $this->split($tests, 2, $timings);

        self::assertSame(0, $status);
        self::assertStringContainsString('3 of 3 test classes recorded', $output);
        self::assertSame(
            [1 => [$this->file('Slow')], 2 => [$this->file('FastOne'), $this->file('FastTwo')]],
            $this->filesPerChunk(2),
        );
    }

    #[Test]
    public function anUnrecordedClassWeighsItsTestsTimesTheRecordedAverage(): void
    {
        // Recorded: 100 s for 2 tests, 50 s per test. "New" has 2 tests and so weighs
        // 100 s - as much as both recorded classes together - and gets a chunk of its
        // own. Weighed by its bare test count (2) it would be the lightest class and
        // join "Recorded" in chunk 1.
        $tests = ['New' => 2, 'Recorded' => 1, 'Other' => 1];
        $timings = ['Recorded' => 50.0, 'Other' => 50.0];

        [$status, $output] = $this->split($tests, 2, $timings);

        self::assertSame(0, $status);
        self::assertStringContainsString('2 of 3 test classes recorded', $output);
        self::assertSame(
            [1 => [$this->file('New')], 2 => [$this->file('Other'), $this->file('Recorded')]],
            $this->filesPerChunk(2),
        );
    }

    #[Test]
    public function neverWritesMoreChunksThanThereAreTestClasses(): void
    {
        [$status, $output] = $this->split(['A' => 3, 'B' => 1], 4);

        self::assertSame(0, $status);
        self::assertStringContainsString('4 chunks asked for, but only 2 test classes: writing 2', $output);
        self::assertFileExists($this->directory . '/FunctionalTests-Job-2.xml');
        self::assertFileDoesNotExist($this->directory . '/FunctionalTests-Job-3.xml');
    }

    #[Test]
    public function anEmptyTestListFailsInsteadOfWritingChunksThatRunNothing(): void
    {
        [$status, $output] = $this->split([], 2);

        self::assertSame(1, $status);
        self::assertStringContainsString('No functional tests found', $output);
        self::assertFileDoesNotExist($this->directory . '/FunctionalTests-Job-1.xml');
    }

    #[Test]
    public function aChunkConfigurationIsTheSuiteConfigurationWithItsFilesAndAnAbsoluteBootstrap(): void
    {
        [$status] = $this->split(['A' => 1], 1);

        self::assertSame(0, $status);
        $configuration = simplexml_load_file($this->directory . '/FunctionalTests-Job-1.xml');
        self::assertNotFalse($configuration);
        self::assertFileExists((string)$configuration['bootstrap']);
        self::assertSame('true', (string)$configuration['failOnDeprecation']);
        self::assertCount(0, $configuration->testsuites->testsuite->directory);
        self::assertSame([$this->file('A')], array_map('strval', iterator_to_array($configuration->testsuites->testsuite->file, false)));
    }

    /**
     * @param array<string, int> $testsPerClass
     * @param array<string, float> $secondsPerClass
     * @return array{int, string}
     */
    private function split(array $testsPerClass, int $numberOfChunks, array $secondsPerClass = []): array
    {
        $list = '<?xml version="1.0"?>' . "\n" . '<testSuite xmlns="https://xml.phpunit.de/testSuite"><tests>';
        foreach ($testsPerClass as $class => $numberOfTests) {
            $list .= sprintf('<testClass name="Fixture\\%s" file="%s">', $class, $this->file($class));
            for ($test = 1; $test <= $numberOfTests; $test++) {
                $list .= sprintf('<testMethod id="Fixture\\%s::test%d" name="test%d"/>', $class, $test, $test);
            }
            $list .= '</testClass>';
        }
        file_put_contents($this->directory . '/tests.xml', $list . '</tests></testSuite>');

        $arguments = [PHP_BINARY, $this->script(), $this->directory . '/tests.xml', (string)$numberOfChunks, $this->directory];
        if ($secondsPerClass !== []) {
            $timings = [];
            foreach ($secondsPerClass as $class => $seconds) {
                $timings[$this->relativeFile($class)] = $seconds;
            }
            file_put_contents($this->directory . '/timings.json', json_encode($timings));
            $arguments[] = $this->directory . '/timings.json';
        }
        $output = [];
        $status = 0;
        exec(implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1', $output, $status);
        return [$status, implode("\n", $output)];
    }

    /**
     * @return array<int, list<string>>
     */
    private function filesPerChunk(int $numberOfChunks): array
    {
        $chunks = [];
        for ($chunk = 1; $chunk <= $numberOfChunks; $chunk++) {
            $configuration = simplexml_load_file($this->directory . '/FunctionalTests-Job-' . $chunk . '.xml');
            self::assertNotFalse($configuration);
            $chunks[$chunk] = array_map('strval', iterator_to_array($configuration->testsuites->testsuite->file, false));
        }
        return $chunks;
    }

    private function file(string $class): string
    {
        return $this->root() . '/' . $this->relativeFile($class);
    }

    private function relativeFile(string $class): string
    {
        return 'packages/fgtclb/fixture/Tests/Functional/' . $class . 'Test.php';
    }

    private function script(): string
    {
        return $this->root() . '/Build/Scripts/splitFunctionalTests.php';
    }

    private function root(): string
    {
        return dirname(__DIR__, 5);
    }
}
