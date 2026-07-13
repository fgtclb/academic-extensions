<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Base',
    'description' => 'Base functionality accross academic extensions.',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'backend' => '13.4.0-13.4.99',
        ],
    ],
];
