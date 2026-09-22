<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Projects List Events',
    'description' => 'Extension listening to the project demand and list events for tests',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_projects' => '3.0.0',
        ],
    ],
];
