<?php

$EM_CONF[$_EXTKEY] = [
    'title' => '(FGTCLB) Academic Projects',
    'description' => 'Academic project pages for TYPO3 with specific structured data and typed sys_categories',
    'category' => 'fe',
    'state' => 'beta',
    'version' => '1.1.5',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'backend' => '11.5.0-11.5.99',
            'extbase' => '11.5.0-11.5.99',
            'fluid' => '11.5.0-11.5.99',
            'install' => '11.5.0-11.5.99',
            'category_types' => '1.1.5',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
