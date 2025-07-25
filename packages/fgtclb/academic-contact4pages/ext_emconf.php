<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Contacts for Pages',
    'description' => 'Role based relations between profiles and pages',
    'category' => 'fe',
    'state' => 'beta',
    'version' => '1.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
            'academic_persons' => '1.2.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
