<?php

$EM_CONF[$_EXTKEY] = [
    'author' => 'FGTCLB',
    'author_company' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'category' => 'plugin',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'extbase' => '12.4.22-13.4.99',
            'academic_persons' => '2.0.2',
            'academic_persons_edit' => '2.0.2',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'description' => 'Adds some configuration for external users providers like Active Directory.',
    'state' => 'beta',
    'title' => 'FGTCLB: Academic Persons Sync',
    'version' => '2.0.2',
];
