<?php

$EM_CONF[$_EXTKEY] = [
    'author' => 'FGTCLB',
    'author_company' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'category' => 'fe',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'academic_persons' => '2.0.2',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'description' => 'Role based relations between profiles and pages',
    'state' => 'beta',
    'title' => 'FGTCLB: Academic Contags for Pages',
    'version' => '2.0.2',
];
