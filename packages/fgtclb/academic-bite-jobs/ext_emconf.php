<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Academic Bite Jobs',
    'description' => 'The Academic Bite Jobs extension shows jobs from the B-Ite Job Board.',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
    ],
    'state' => 'stable',
    'version' => '2.0.1',
    'clearCacheOnLoad' => true,
    'category' => 'fe,be',
    'author' => 'Riad Zejnilagic Trumic',
    'author_company' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'autoload' => [
        'psr-4' => [
            'Fgtclb\\AcademicBiteJobs\\' => 'Classes/',
        ],
    ],
];
