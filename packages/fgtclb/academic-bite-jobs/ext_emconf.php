<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Bite Jobs',
    'description' => 'The Academic Bite Jobs extension shows jobs from the B-Ite Job Board.',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'academic_base' => '3.0.0',
        ],
    ],
];
