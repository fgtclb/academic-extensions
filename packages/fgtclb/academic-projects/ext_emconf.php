<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Projects',
    'description' => 'Academic project pages for TYPO3 with specific structured data and typed sys_categories',
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
            'extbase' => '13.4.0-13.4.99',
            'fluid' => '13.4.0-13.4.99',
            'install' => '13.4.0-13.4.99',
            'academic_base' => '3.0.0',
            'category_types' => '3.0.0',
        ],
    ],
];
