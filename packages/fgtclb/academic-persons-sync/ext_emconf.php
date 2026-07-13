<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Persons Sync',
    'description' => 'Adds some configuration for external users providers like Active Directory.',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'extbase' => '13.4.0-13.4.99',
            'academic_base' => '3.0.0',
            'academic_persons' => '3.0.0',
            'academic_persons_edit' => '3.0.0',
        ],
    ],
];
