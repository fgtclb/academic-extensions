<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Contacts for Pages',
    'description' => 'Role based relations between profiles and pages',
    'category' => 'fe',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'academic_persons' => '0.1.0-0.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
