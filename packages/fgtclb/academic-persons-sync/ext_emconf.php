<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Persons Sync',
    'description' => 'Adds some configuration for external users providers like Active Directory.',
    'category' => 'plugin',
    'author' => 'Tim Schreiner',
    'author_email' => 'tim.schreiner@km2.de',
    'author_company' => 'FGTCLB',
    'state' => 'beta',
    'version' => '2.0.1',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'extbase' => '12.4.0-13.4.99',
            'academic_persons' => '2.0.1',
            'academic_persons_edit' => '2.0.1',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
