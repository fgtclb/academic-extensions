<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Upgrade check shared partials',
    'description' => 'A shared partial package the upgrade check fixture extension requires, two links from the checked one',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_base' => '3.0.0',
        ],
    ],
];
