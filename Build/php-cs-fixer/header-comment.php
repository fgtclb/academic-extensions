<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

/*
 * This file is part of the TYPO3 CMS project.
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

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$finder = Finder::create()
    ->name('*.php')
    ->in([
        __DIR__ . '/../../packages/fgtclb/',
        __DIR__ . '/../../Build',
    ])
    ->exclude('Acceptance/Support/_generated') // EXT:core
    ->exclude('node_modules')
    // Configuration files do not need header comments
    ->exclude('Configuration')
    ->notName('*locallang*.php')
    ->notName('ext_localconf.php')
    ->notName('ext_tables.php')
    ->notName('ext_emconf.php')
    // ClassAliasMap files do not need header comments
    ->notName('ClassAliasMap.php')
    // CodeSnippets and Examples in Documentation do not need header comments
    ->exclude('Documentation')
;

$headerComment = <<<COMMENT
This file is part of the fgtclb/academic extension collection.

It is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License, either version 2
of the License, or any later version.

For the full copyright and license information, please read the
LICENSE.txt file that was distributed with this source code.

The TYPO3 project - inspiring people to share!
COMMENT;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(false)
    ->setRules([
        'no_extra_blank_lines' => true,
        'header_comment' => [
            'header' => $headerComment,
            'comment_type' => 'comment',
            'separate' => 'both',
            'location' => 'after_declare_strict',
        ],
    ])
    ->setFinder($finder);
