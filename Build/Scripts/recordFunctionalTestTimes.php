<?php

declare(strict_types=1);

/*
 * This file is part of the fgtclb/academic extension collection.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Writes the timings file "splitFunctionalTests.php" balances the chunks by.
 *
 *   php Build/Scripts/recordFunctionalTestTimes.php <timings.json> <junit.xml> [<junit.xml> ...]
 *
 * Reads the JUnit logs of a functional run - every chunk of "runTests.sh -j"
 * writes one to ".Build/functional-junit/", and the CI jobs upload them - and
 * writes the duration of each test class, in seconds, keyed by its file path
 * relative to the repository root. A class that appears in several logs is
 * added up. The result is committed as "Build/phpunit/FunctionalTestTimes-
 * <dbms>.json", one file per DBMS because the same class costs very different
 * times on each, and refreshed by hand when the balance drifts: the file is an
 * input for the split, never a gate, so a stale one costs time and nothing else.
 * "runTests.sh -s recordFunctionalTestTimes" runs it in the PHP container.
 *
 * The paths are cut at "packages", which every test directory the suite reads
 * sits below, so logs from a CI runner or another checkout record the same keys.
 */
exit(main($argv));

/**
 * @param list<string> $argv
 */
function main(array $argv): int
{
    if (count($argv) < 3) {
        fwrite(STDERR, 'Usage: php ' . $argv[0] . ' <timings.json> <junit.xml> [<junit.xml> ...]' . PHP_EOL);
        return 1;
    }
    $timings = [];
    foreach (array_slice($argv, 2) as $junitFile) {
        $junit = new DOMDocument();
        if (!is_file($junitFile) || !@$junit->load($junitFile)) {
            fwrite(STDERR, 'Cannot read ' . $junitFile . PHP_EOL);
            return 1;
        }
        foreach ((new DOMXPath($junit))->query('//testsuite[@file]') ?: [] as $testSuite) {
            if (!$testSuite instanceof DOMElement) {
                continue;
            }
            $file = $testSuite->getAttribute('file');
            $position = strpos($file, '/packages');
            if ($position === false) {
                continue;
            }
            $relativeFile = substr($file, $position + 1);
            $timings[$relativeFile] = ($timings[$relativeFile] ?? 0.0) + (float)$testSuite->getAttribute('time');
        }
    }
    if ($timings === []) {
        fwrite(STDERR, 'No test class durations found' . PHP_EOL);
        return 1;
    }
    ksort($timings);
    $timings = array_map(static fn(float $seconds): float => round($seconds, 2), $timings);
    file_put_contents($argv[1], json_encode($timings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . PHP_EOL);
    echo sprintf('%d test classes, %.0f s, written to %s', count($timings), array_sum($timings), $argv[1]) . PHP_EOL;
    return 0;
}
