<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Contags for Pages',
    'description' => 'Role based relations between profiles and pages',
    'version' => '2.3.4',
    'category' => 'fe',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_base' => '2.3.4',
            'academic_persons' => '2.3.4',
        ],
    ],
];
