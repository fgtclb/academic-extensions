<?php

/*
 * This file is part of the "academic_persons_sync" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Persons Sync',
    'description' => 'Adds some configuration for external users providers like Active Directory.',
    'category' => 'plugin',
    'author' => 'Tim Schreiner',
    'author_email' => 'tim.schreiner@km2.de',
    'author_company' => 'FGTCLB',
    'state' => 'beta',
    'version' => '0.2.0',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'academic_persons' => '0.2.0 - 0.2.99',
            'academic_persons_edit' => '0.2.0 - 0.2.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
