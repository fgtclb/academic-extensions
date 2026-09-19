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
 * Fails a parallel functional run that executed another number of tests than
 * phpunit listed for it.
 *
 *   php Build/Scripts/checkFunctionalTestCount.php <list-tests.xml> <junit.xml> [<junit.xml> ...]
 *
 * "runTests.sh -j" splits the list phpunit writes with "--list-tests-xml" into
 * chunks and runs them in parallel; each chunk writes a JUnit log. Every chunk
 * can be green while tests went missing on the way - a class the split left
 * out, a chunk configuration that points at the wrong file, a chunk that
 * filtered more than it should - and a green run shows none of that. This
 * compares the number of tests the list holds with the number the JUnit logs
 * report, skipped ones included on both sides, and fails on any difference.
 */
exit(main($argv));

/**
 * @param list<string> $argv
 */
function main(array $argv): int
{
    if (count($argv) < 3) {
        fwrite(STDERR, 'Usage: php ' . $argv[0] . ' <list-tests.xml> <junit.xml> [<junit.xml> ...]' . PHP_EOL);
        return 1;
    }
    $list = new DOMDocument();
    if (!is_file($argv[1]) || !@$list->load($argv[1])) {
        fwrite(STDERR, 'Cannot read ' . $argv[1] . PHP_EOL);
        return 1;
    }
    $xpath = new DOMXPath($list);
    $xpath->registerNamespace('t', 'https://xml.phpunit.de/testSuite');
    $listed = ($xpath->query('//t:testClass/t:testMethod') ?: new DOMNodeList())->length;

    $executed = 0;
    foreach (array_slice($argv, 2) as $junitFile) {
        $junit = new DOMDocument();
        if (!is_file($junitFile) || !@$junit->load($junitFile)) {
            fwrite(STDERR, 'Cannot read ' . $junitFile . PHP_EOL);
            return 1;
        }
        // The outermost suite of a log counts every test of the run once.
        $suite = (new DOMXPath($junit))->query('/testsuites/testsuite')?->item(0);
        $executed += $suite instanceof DOMElement ? (int)$suite->getAttribute('tests') : 0;
    }

    if ($executed !== $listed) {
        fwrite(STDERR, sprintf(
            'The chunks executed %d tests, but phpunit listed %d for this run. Tests were lost or run twice on the way through the split.',
            $executed,
            $listed,
        ) . PHP_EOL);
        return 1;
    }
    echo sprintf('All %d listed tests executed, in %d chunks', $listed, count($argv) - 2) . PHP_EOL;
    return 0;
}
