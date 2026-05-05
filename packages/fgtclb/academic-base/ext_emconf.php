<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Base',
    'description' => 'Base functionality accross academic extensions.',
    'version' => '2.3.4',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'backend' => '12.4.22-13.4.99',
        ],
    ],
];
