<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons Language Files',
    'description' => 'Provides extension with custom academic extension settings',
    'category' => 'fe,be',
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
