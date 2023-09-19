<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Academic Bite Jobs',
    'description' => 'The Academic Bite Jobs extension shows jobs from the B-Ite Job Board.',
    'category' => 'fe,be',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
    ],
    'author' => 'Riad Zejnilagic Trumic',
    'author_company' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'autoload' => [
        'psr-4' => [
            'Fgtclb\\AcademicBiteJobs\\' => 'Classes/',
        ],
    ],
];
