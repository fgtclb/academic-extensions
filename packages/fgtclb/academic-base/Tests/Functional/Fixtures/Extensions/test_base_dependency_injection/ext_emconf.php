<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Base Dependency Injection Tests',
    'description' => 'Provide classes to test dependency injection',
    'version' => '2.3.4',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_base' => '2.3.4',
        ],
    ],
];
