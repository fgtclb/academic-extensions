<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic configuration check',
    'description' => 'An academic extension the configuration check finds stale configuration for',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
        ],
    ],
];
