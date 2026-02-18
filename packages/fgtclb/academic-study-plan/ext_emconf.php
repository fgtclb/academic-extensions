<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Academic Study Plan',
    'description' => 'TYPO3 extension for building and displaying academic study plans with semesters, modules, and categorization features.',
    'category' => 'fe',
    'author' => 'FGTCLB',
    'author_company' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'state' => 'beta',
    'version' => '2.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.4.99',
            'backend' => '12.0.0-13.4.99',
            'academic_base' => '2.1.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
