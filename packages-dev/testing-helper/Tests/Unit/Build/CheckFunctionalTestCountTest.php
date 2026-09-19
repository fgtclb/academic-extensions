<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\Tests\Unit\Build;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * "Build/Scripts/checkFunctionalTestCount.php", the guard of "runTests.sh -j"
 * against a parallel run that is green because tests went missing.
 *
 * The script is run rather than included: it ends in `exit(main($argv))`.
 */
final class CheckFunctionalTestCountTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/check-functional-test-count-' . bin2hex(random_bytes(4));
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
    public function passesWhenTheChunksExecutedEveryListedTest(): void
    {
        $this->list(['A' => 3, 'B' => 2]);
        $this->junit('junit-1.xml', 3);
        $this->junit('junit-2.xml', 2);

        [$status, $output] = $this->check('junit-1.xml', 'junit-2.xml');

        self::assertSame(0, $status, $output);
        self::assertStringContainsString('All 5 listed tests executed, in 2 chunks', $output);
    }

    #[Test]
    public function failsWhenTheChunksExecutedFewerTestsThanListed(): void
    {
        $this->list(['A' => 3, 'B' => 2]);
        $this->junit('junit-1.xml', 3);
        $this->junit('junit-2.xml', 0);

        [$status, $output] = $this->check('junit-1.xml', 'junit-2.xml');

        self::assertSame(1, $status);
        self::assertStringContainsString('The chunks executed 3 tests, but phpunit listed 5', $output);
    }

    #[Test]
    public function failsWhenTheChunksExecutedMoreTestsThanListed(): void
    {
        $this->list(['A' => 2]);
        $this->junit('junit-1.xml', 2);
        $this->junit('junit-2.xml', 2);

        [$status, $output] = $this->check('junit-1.xml', 'junit-2.xml');

        self::assertSame(1, $status);
        self::assertStringContainsString('The chunks executed 4 tests, but phpunit listed 2', $output);
    }

    #[Test]
    public function failsWhenAChunkLeftNoLog(): void
    {
        $this->list(['A' => 1]);

        [$status, $output] = $this->check('missing.xml');

        self::assertSame(1, $status);
        self::assertStringContainsString('Cannot read', $output);
    }

    /**
     * @param array<string, int> $testsPerClass
     */
    private function list(array $testsPerClass): void
    {
        $xml = '<?xml version="1.0"?>' . "\n" . '<testSuite xmlns="https://xml.phpunit.de/testSuite"><tests>';
        foreach ($testsPerClass as $class => $numberOfTests) {
            $xml .= sprintf('<testClass name="Fixture\\%s" file="/checkout/%sTest.php">', $class, $class);
            for ($test = 1; $test <= $numberOfTests; $test++) {
                $xml .= sprintf('<testMethod id="Fixture\\%s::test%d" name="test%d"/>', $class, $test, $test);
            }
            $xml .= '</testClass>';
        }
        file_put_contents($this->directory . '/tests.xml', $xml . '</tests></testSuite>');
    }

    /**
     * The shape phpunit writes: an outer suite counting every test of the run,
     * holding one suite per test class.
     */
    private function junit(string $name, int $numberOfTests): void
    {
        file_put_contents(
            $this->directory . '/' . $name,
            sprintf(
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<testsuites><testsuite name="Functional tests" tests="%d" time="1">'
                . '<testsuite name="Fixture" file="/checkout/ATest.php" tests="%d" time="1"/>'
                . '</testsuite></testsuites>',
                $numberOfTests,
                $numberOfTests,
            ),
        );
    }

    /**
     * @return array{int, string}
     */
    private function check(string ...$logs): array
    {
        $arguments = [PHP_BINARY, dirname(__DIR__, 5) . '/Build/Scripts/checkFunctionalTestCount.php', $this->directory . '/tests.xml'];
        foreach ($logs as $log) {
            $arguments[] = $this->directory . '/' . $log;
        }
        $output = [];
        $status = 0;
        exec(implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1', $output, $status);
        return [$status, implode("\n", $output)];
    }
}
