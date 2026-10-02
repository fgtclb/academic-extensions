<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Upgrade check project',
    'description' => 'A project site package overriding templates of the upgrade check fixture extension',
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
            'academic_base' => '3.0.0',
        ],
    ],
];
