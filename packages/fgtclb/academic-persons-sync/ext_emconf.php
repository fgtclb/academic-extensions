<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Persons Sync',
    'description' => 'Adds some configuration for external users providers like Active Directory.',
    'version' => '2.3.5',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'extbase' => '12.4.22-13.4.99',
            'academic_base' => '2.3.5',
            'academic_persons_edit' => '2.3.5',
            'academic_persons' => '2.3.5',
        ],
    ],
];
