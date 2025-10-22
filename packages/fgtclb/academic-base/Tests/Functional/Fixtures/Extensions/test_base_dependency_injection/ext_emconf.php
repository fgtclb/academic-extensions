<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Base Dependency Injection Tests',
    'description' => 'Provide classes to test dependency injection',
    'category' => 'plugin',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => 'web-vision GmbH',
    'state' => 'beta',
    'version' => '2.0.2',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_base' => '2.0.2',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
