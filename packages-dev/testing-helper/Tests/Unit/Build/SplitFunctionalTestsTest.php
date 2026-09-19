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
 * that the heavy classes do not pile up in one chunk, and - PHPUnit 10 does not
 * apply "--exclude-group" to its list - that an excluded test runs in no chunk.
 * The listed classes are the fixtures in `Fixtures/`, because the splitter looks
 * their files up with the composer class loader. The script is run rather than
 * included: it ends in `exit(main($argv))`.
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
        $tests = ['Alpha' => 7, 'Bravo' => 3, 'Charlie' => 3, 'Delta' => 2, 'Kilo' => 1, 'Lima' => 1];

        [$status] = $this->split($tests, 3);

        self::assertSame(0, $status);
        $all = array_merge(...array_values($this->filesPerChunk(3)));
        sort($all);
        self::assertSame(array_map($this->file(...), array_keys($tests)), $all);
    }

    #[Test]
    public function withoutTimingsTheHeaviestClassGoesFirstIntoTheLightestChunk(): void
    {
        [$status] = $this->split(['Alpha' => 10, 'Bravo' => 6, 'Charlie' => 5, 'Delta' => 5], 2);

        self::assertSame(0, $status);
        // 10 -> 1, 6 -> 2, 5 -> 2 (6 < 10), 5 -> 1 (10 < 11)
        self::assertSame(
            [1 => [$this->file('Alpha'), $this->file('Delta')], 2 => [$this->file('Bravo'), $this->file('Charlie')]],
            $this->filesPerChunk(2),
        );
    }

    #[Test]
    public function recordedDurationsOutweighTheNumberOfTests(): void
    {
        // One slow class with a single test, two fast ones with many.
        $tests = ['Alpha' => 1, 'Bravo' => 50, 'Charlie' => 50];
        $timings = ['Alpha' => 100.0, 'Bravo' => 10.0, 'Charlie' => 10.0];

        [$status, $output] = $this->split($tests, 2, [], $timings);

        self::assertSame(0, $status);
        self::assertStringContainsString('3 of 3 test classes recorded', $output);
        self::assertSame(
            [1 => [$this->file('Alpha')], 2 => [$this->file('Bravo'), $this->file('Charlie')]],
            $this->filesPerChunk(2),
        );
    }

    #[Test]
    public function anUnrecordedClassWeighsItsTestsTimesTheRecordedAverage(): void
    {
        // Recorded: 100 s for 2 tests, 50 s per test. "Alpha" has 2 tests and so
        // weighs 100 s - as much as both recorded classes together - and gets a
        // chunk of its own. Weighed by its bare test count (2) it would be the
        // lightest class and join "Bravo" in chunk 1.
        $tests = ['Alpha' => 2, 'Bravo' => 1, 'Charlie' => 1];
        $timings = ['Bravo' => 50.0, 'Charlie' => 50.0];

        [$status, $output] = $this->split($tests, 2, [], $timings);

        self::assertSame(0, $status);
        self::assertStringContainsString('2 of 3 test classes recorded', $output);
        self::assertSame(
            [1 => [$this->file('Alpha')], 2 => [$this->file('Bravo'), $this->file('Charlie')]],
            $this->filesPerChunk(2),
        );
    }

    #[Test]
    public function testsOfAnExcludedGroupAreLeftOut(): void
    {
        // "Alpha" has 4 tests, 3 of them excluded; "Bravo" has 2, both excluded, so
        // its class does not run at all and must not reach a chunk.
        $tests = ['Alpha' => 4, 'Bravo' => 2, 'Charlie' => 2];
        $groups = ['Alpha' => [1 => 'not-core-12', 2 => 'default,not-sqlite', 3 => 'not-core-12'], 'Bravo' => [1 => 'not-core-12', 2 => 'not-core-12']];

        [$status, $output] = $this->split($tests, 2, $groups, [], 'seed-manifest-update,not-sqlite,not-core-12');

        self::assertSame(0, $status);
        self::assertSame([1 => [$this->file('Charlie')], 2 => [$this->file('Alpha')]], $this->filesPerChunk(2));
        self::assertStringContainsString('Chunk 2/2: 1 test classes, 1 tests', $output);
    }

    #[Test]
    public function aClassTheClassLoaderCannotFindFails(): void
    {
        [$status, $output] = $this->split(['DoesNotExist' => 1], 1);

        self::assertSame(1, $status);
        self::assertStringContainsString('No file found for the test class', $output);
    }

    #[Test]
    public function neverWritesMoreChunksThanThereAreTestClasses(): void
    {
        [$status, $output] = $this->split(['Alpha' => 3, 'Bravo' => 1], 4);

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
        [$status] = $this->split(['Alpha' => 1], 1);

        self::assertSame(0, $status);
        $configuration = simplexml_load_file($this->directory . '/FunctionalTests-Job-1.xml');
        self::assertNotFalse($configuration);
        self::assertFileExists((string)$configuration['bootstrap']);
        self::assertSame('true', (string)$configuration['failOnDeprecation']);
        self::assertCount(0, $configuration->testsuites->testsuite->directory);
        self::assertSame([$this->file('Alpha')], array_map('strval', iterator_to_array($configuration->testsuites->testsuite->file, false)));
    }

    /**
     * The shape PHPUnit 10 writes: no namespace, no file, and the groups of every
     * test - "default" unless the test names its own.
     *
     * @param array<string, int> $testsPerClass
     * @param array<string, array<int, string>> $groupsPerTest class => test number => groups
     * @param array<string, float> $secondsPerClass
     * @return array{int, string}
     */
    private function split(array $testsPerClass, int $numberOfChunks, array $groupsPerTest = [], array $secondsPerClass = [], string $excludedGroups = 'seed-manifest-update,not-sqlite,not-core-13'): array
    {
        $list = '<?xml version="1.0"?>' . "\n" . '<tests>';
        foreach ($testsPerClass as $class => $numberOfTests) {
            $list .= sprintf('<testCaseClass name="%s">', $this->className($class));
            for ($test = 1; $test <= $numberOfTests; $test++) {
                $list .= sprintf(
                    '<testCaseMethod id="%s::test%d" name="test%d" groups="%s"/>',
                    $this->className($class),
                    $test,
                    $test,
                    $groupsPerTest[$class][$test] ?? 'default',
                );
            }
            $list .= '</testCaseClass>';
        }
        file_put_contents($this->directory . '/tests.xml', $list . '</tests>');

        $arguments = [PHP_BINARY, $this->root() . '/Build/Scripts/splitFunctionalTests.php', $this->directory . '/tests.xml', (string)$numberOfChunks, $this->directory, $excludedGroups];
        if ($secondsPerClass !== []) {
            $timings = [];
            foreach ($secondsPerClass as $class => $seconds) {
                $timings[substr($this->file($class), strlen($this->root()) + 1)] = $seconds;
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

    private function className(string $class): string
    {
        return __NAMESPACE__ . '\\Fixtures\\' . $class;
    }

    private function file(string $class): string
    {
        return __DIR__ . '/Fixtures/' . $class . '.php';
    }

    private function root(): string
    {
        return dirname(__DIR__, 5);
    }
}
