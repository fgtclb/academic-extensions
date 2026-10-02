<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Upgrade check upstream',
    'description' => 'The extension the upgrade check compares an override folder with',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.35-14.3.99',
            'core' => '13.4.35-14.3.99',
            'test_upgrade_check_shared' => '3.0.0',
        ],
    ],
];
