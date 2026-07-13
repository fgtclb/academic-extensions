<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Academic Study Plan',
    'description' => 'TYPO3 extension for building and displaying academic study plans with semesters, modules, and categorization features.',
    'version' => '3.0.0',
    'category' => 'fe',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'backend' => '13.4.0-13.4.99',
            'academic_base' => '3.0.0',
        ],
    ],
];
