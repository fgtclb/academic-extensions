<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Persons Sync',
    'description' => 'Adds some configuration for external users providers like Active Directory.',
    'category' => 'plugin',
    'author' => 'Tim Schreiner',
    'author_email' => 'tim.schreiner@km2.de',
    'author_company' => 'FGTCLB',
    'state' => 'beta',
    'version' => '1.1.5',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
            'extbase' => '11.5.0-12.4.99',
            'academic_persons' => '1.1.5',
            'academic_persons_edit' => '1.1.5',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
