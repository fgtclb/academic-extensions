<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Base frontend icons',
    'description' => 'Icons registered for the frontend icon registry of academic_base, from a file and from a listener',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.35-14.3.99',
            'core' => '13.4.35-14.3.99',
            'academic_base' => '3.0.0',
        ],
    ],
];
