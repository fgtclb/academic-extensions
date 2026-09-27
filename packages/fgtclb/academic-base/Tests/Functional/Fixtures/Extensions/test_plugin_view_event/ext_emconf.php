<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Plugin View Event',
    'description' => 'Extension listening to the plugin view event of every academic plugin for tests',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_base' => '3.0.0',
            'academic_bite_jobs' => '3.0.0',
            'academic_contacts4pages' => '3.0.0',
            'academic_jobs' => '3.0.0',
            'academic_partners' => '3.0.0',
            'academic_persons' => '3.0.0',
            'academic_programs' => '3.0.0',
            'academic_projects' => '3.0.0',
        ],
    ],
];
