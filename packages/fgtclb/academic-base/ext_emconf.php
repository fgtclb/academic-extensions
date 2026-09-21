<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Base',
    'description' => 'Base functionality across academic extensions.',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'backend' => '13.4.0-14.3.99',
            'extbase' => '13.4.0-14.3.99',
            // The "--site" mode of "academic:upgrade:check" builds a frontend
            // environment for the site through its state manager.
            'environment_state_manager' => '2.0.1-2.99.99',
        ],
    ],
];
