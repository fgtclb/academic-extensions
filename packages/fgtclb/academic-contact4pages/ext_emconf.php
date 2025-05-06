<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Contacts for Pages',
    'description' => 'Role based relations between profiles and pages',
    'category' => 'fe',
    'state' => 'beta',
    'version' => '2.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'academic_persons' => '2.0.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
