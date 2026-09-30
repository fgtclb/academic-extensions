<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Contacts4Pages Page Contacts Listener',
    'description' => 'Extension listening to the page contacts event for tests',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_contacts4pages' => '3.0.0',
        ],
    ],
];
