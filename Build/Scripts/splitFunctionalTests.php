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
 * Splits the functional test suite into chunks that take about the same time.
 *
 * Modelled on "Build/Scripts/splitFunctionalTests.php" of TYPO3 Core, with two
 * differences. The tests are not found by parsing files but read from the list
 * phpunit writes with "--list-tests-xml", which has every data set resolved. And
 * a test class weighs its recorded duration rather than its number of tests,
 * when a timings file is given.
 *
 *   php Build/Scripts/splitFunctionalTests.php <list-tests.xml> <number-of-chunks> <output-directory> <excluded-groups> [<timings.json>]
 *
 * PHPUNIT 10
 *
 * This branch runs PHPUnit 10, whose list differs from the one of PHPUnit 11 in
 * two ways that matter here. It does not apply "--exclude-group", so it holds the
 * tests of "not-core-NN" and "not-<dbms>" as well; it carries the groups of every
 * test instead, and <excluded-groups> - the comma separated value "runTests.sh"
 * passes to "--exclude-group" - drops them here, so a chunk holds the tests that
 * actually run on this core version and DBMS. And it names test classes without
 * their files, which are looked up with the composer class loader of the
 * repository root: "findFile()" resolves the PSR-4 path without loading the
 * class, which matters because a class written for the other core version cannot
 * be loaded at all.
 *
 * Writes "FunctionalTests-Job-<n>.xml" for n = 1 .. <number-of-chunks> into the
 * output directory. Each is a copy of "Build/phpunit/FunctionalTests.xml" whose
 * test suite lists the files of that chunk, with the bootstrap as an absolute
 * path so the copy works from any directory. "runTests.sh -j" calls it.
 *
 * WHY A CLASS IS NEVER SPLIT
 *
 * Each functional test class gets its own TYPO3 instance and its own database,
 * set up once for all of its tests. Two chunks sharing a class would set up the
 * same instance twice, in the same directory. The heaviest classes are placed
 * first, each into the chunk that is lightest so far.
 *
 * WHY RECORDED DURATIONS
 *
 * The test count is a poor predictor. A handful of classes of "packages-dev/"
 * import the whole development seed and take minutes each, so a split by test
 * count left the slowest chunk 12 to 30 % behind the average in CI, and the job
 * waits for the slowest chunk. The timings file ("Build/phpunit/
 * FunctionalTestTimes-<dbms>.json", written by "recordFunctionalTestTimes.php")
 * maps a file path relative to the repository root to seconds. A class it does
 * not know yet weighs its number of tests times the average recorded duration of
 * one test, so a stale file costs balance and never a test.
 */
exit(main($argv));

/**
 * @param list<string> $argv
 */
function main(array $argv): int
{
    if (count($argv) < 5 || count($argv) > 6 || !is_file($argv[1]) || (int)$argv[2] < 1) {
        fwrite(STDERR, 'Usage: php ' . $argv[0] . ' <list-tests.xml> <number-of-chunks> <output-directory> <excluded-groups> [<timings.json>]' . PHP_EOL);
        return 1;
    }
    $numberOfChunks = (int)$argv[2];
    $outputDirectory = $argv[3];
    $excludedGroups = array_values(array_filter(explode(',', $argv[4])));
    $timingsFile = $argv[5] ?? '';
    $rootDirectory = dirname(__DIR__, 2);

    try {
        $testsPerFile = readTestsPerFile($argv[1], $excludedGroups, $rootDirectory);
    } catch (RuntimeException $exception) {
        fwrite(STDERR, $exception->getMessage() . PHP_EOL);
        return 1;
    }
    // A list phpunit could not fill must not become chunks that run nothing and pass.
    if (array_sum($testsPerFile) === 0) {
        fwrite(STDERR, 'No functional tests found in ' . $argv[1] . PHP_EOL);
        return 1;
    }

    $weightPerFile = $testsPerFile;
    $unit = 'tests';
    if ($timingsFile !== '' && is_file($timingsFile)) {
        $timings = json_decode((string)file_get_contents($timingsFile), true, 512, JSON_THROW_ON_ERROR);
        $weightPerFile = weighByTimings($testsPerFile, $timings, $rootDirectory);
        $unit = 's';
        echo sprintf(
            'Balanced by %s: %d of %d test classes recorded',
            $timingsFile,
            count(array_intersect_key(relativeKeys($testsPerFile, $rootDirectory), $timings)),
            count($testsPerFile),
        ) . PHP_EOL;
    }

    $chunks = distribute($weightPerFile, $numberOfChunks);

    if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0777, true) && !is_dir($outputDirectory)) {
        fwrite(STDERR, 'Cannot create ' . $outputDirectory . PHP_EOL);
        return 1;
    }
    foreach ($chunks as $index => $files) {
        writeConfiguration($rootDirectory . '/Build/phpunit/FunctionalTests.xml', $outputDirectory . '/FunctionalTests-Job-' . $index . '.xml', $files);
        echo sprintf(
            'Chunk %d/%d: %d test classes, %d tests, weight %.0f %s',
            $index,
            $numberOfChunks,
            count($files),
            array_sum(array_intersect_key($testsPerFile, array_flip($files))),
            array_sum(array_intersect_key($weightPerFile, array_flip($files))),
            $unit,
        ) . PHP_EOL;
    }
    return 0;
}

/**
 * The phpunit 10 list: <testCaseClass name="…"> holding one <testCaseMethod
 * groups="…"> per test and data set, without a namespace and without files.
 *
 * @param list<string> $excludedGroups
 * @return array<string, int> absolute file path => number of tests that run
 */
function readTestsPerFile(string $listFile, array $excludedGroups, string $rootDirectory): array
{
    $list = new DOMDocument();
    if (!@$list->load($listFile)) {
        return [];
    }
    $classLoader = require $rootDirectory . '/.Build/vendor/autoload.php';
    $xpath = new DOMXPath($list);
    $testsPerFile = [];
    foreach ($xpath->query('//testCaseClass') ?: [] as $testCaseClass) {
        if (!$testCaseClass instanceof DOMElement) {
            continue;
        }
        $numberOfTests = countTests($xpath, $testCaseClass, $excludedGroups);
        if ($numberOfTests === 0) {
            continue;
        }
        $className = $testCaseClass->getAttribute('name');
        $file = $classLoader->findFile($className);
        if ($file === false || ($file = realpath($file)) === false) {
            throw new RuntimeException('No file found for the test class ' . $className);
        }
        $testsPerFile[$file] = ($testsPerFile[$file] ?? 0) + $numberOfTests;
    }
    return $testsPerFile;
}

/**
 * @param list<string> $excludedGroups
 */
function countTests(DOMXPath $xpath, DOMElement $testCaseClass, array $excludedGroups): int
{
    $numberOfTests = 0;
    foreach ($xpath->query('testCaseMethod', $testCaseClass) ?: [] as $testCaseMethod) {
        if ($testCaseMethod instanceof DOMElement
            && array_intersect(explode(',', $testCaseMethod->getAttribute('groups')), $excludedGroups) === []
        ) {
            $numberOfTests++;
        }
    }
    return $numberOfTests;
}

/**
 * @param array<string, int> $testsPerFile
 * @param array<string, float|int> $timings file relative to the repository root => seconds
 * @return array<string, float>
 */
function weighByTimings(array $testsPerFile, array $timings, string $rootDirectory): array
{
    $relativeFiles = relativeKeys($testsPerFile, $rootDirectory);
    $recordedSeconds = 0.0;
    $recordedTests = 0;
    foreach ($relativeFiles as $relativeFile => $file) {
        if (isset($timings[$relativeFile])) {
            $recordedSeconds += (float)$timings[$relativeFile];
            $recordedTests += $testsPerFile[$file];
        }
    }
    $secondsPerTest = $recordedTests > 0 ? $recordedSeconds / $recordedTests : 1.0;
    $weightPerFile = [];
    foreach ($relativeFiles as $relativeFile => $file) {
        $weightPerFile[$file] = (float)($timings[$relativeFile] ?? $testsPerFile[$file] * $secondsPerTest);
    }
    return $weightPerFile;
}

/**
 * @param array<string, int|float> $perFile
 * @return array<string, string> file relative to the repository root => absolute file
 */
function relativeKeys(array $perFile, string $rootDirectory): array
{
    $relative = [];
    foreach (array_keys($perFile) as $file) {
        $relative[str_starts_with($file, $rootDirectory . '/') ? substr($file, strlen($rootDirectory) + 1) : $file] = $file;
    }
    return $relative;
}

/**
 * Heaviest first into the lightest chunk. The file name breaks ties, so every
 * run computes the same split from the same input.
 *
 * @param array<string, int|float> $weightPerFile
 * @return array<int, list<string>> chunk number, starting at 1 => files
 */
function distribute(array $weightPerFile, int $numberOfChunks): array
{
    uksort($weightPerFile, static fn(string $a, string $b): int => [$weightPerFile[$b], $a] <=> [$weightPerFile[$a], $b]);
    $weights = array_fill(1, $numberOfChunks, 0.0);
    $chunks = array_fill(1, $numberOfChunks, []);
    foreach ($weightPerFile as $file => $weight) {
        $lightest = array_keys($weights, min($weights), true)[0];
        $weights[$lightest] += $weight;
        $chunks[$lightest][] = $file;
    }
    foreach ($chunks as &$files) {
        sort($files);
    }
    unset($files);
    return $chunks;
}

/**
 * @param list<string> $files
 */
function writeConfiguration(string $template, string $target, array $files): void
{
    $configuration = new DOMDocument();
    $configuration->preserveWhiteSpace = false;
    $configuration->formatOutput = true;
    $configuration->load($template);
    $phpunit = $configuration->documentElement;
    $phpunit->setAttribute('bootstrap', dirname($template) . '/' . $phpunit->getAttribute('bootstrap'));
    $testSuite = $configuration->getElementsByTagName('testsuite')->item(0);
    while ($testSuite->firstChild !== null) {
        $testSuite->removeChild($testSuite->firstChild);
    }
    foreach ($files as $file) {
        $testSuite->appendChild($configuration->createElement('file', $file));
    }
    $configuration->save($target);
}
