<?php

declare(strict_types=1);

return [
    \FGTCLB\AcademicProjects\Domain\Model\Project::class => [
        'tableName' => 'pages',
        'properties' => [
            'doktype' => [
                'fieldName' => 'doktype',
            ],
            'lastUpdated' => [
                'fieldName' => 'lastUpdated',
            ],
            'media' => [
                'fieldName' => 'media',
            ],
            'projectTitle' => [
                'fieldName' => 'tx_academicprojects_project_title',
            ],
            'shortDescription' => [
                'fieldName' => 'tx_academicprojects_short_description',
            ],
            'startDate' => [
                'fieldName' => 'tx_academicprojects_start_date',
            ],
            'endDate' => [
                'fieldName' => 'tx_academicprojects_end_date',
            ],
            'budget' => [
                'fieldName' => 'tx_academicprojects_budget',
            ],
            'funders' => [
                'fieldName' => 'tx_academicprojects_funders',
            ],
        ],
    ],
];
