<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Base wizard content elements',
    'description' => 'Three content elements in the academic item group and two in core groups, for the new content element wizard',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_base' => '3.0.0',
        ],
    ],
];
