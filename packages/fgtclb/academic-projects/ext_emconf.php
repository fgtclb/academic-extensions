<?php

$EM_CONF[$_EXTKEY] = [
    'title' => '(FGTCLB) Academic Projects',
    'description' => 'Academic project pages for TYPO3 with specific structured data and typed sys_categories',
    'category' => 'fe',
    'state' => 'beta',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'backend' => '12.4.0-13.4.99',
            'extbase' => '12.4.0-13.4.99',
            'fluid' => '12.4.0-13.4.99',
            'install' => '12.4.0-13.4.99',
            'category_types' => '2.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
