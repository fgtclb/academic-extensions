<?php

declare(strict_types=1);

return [
    \FGTCLB\AcademicProjects\Domain\Model\Project::class => [
        'tableName' => 'pages',
        'properties' => [
            'lastUpdated' => [
                'fieldName' => 'lastUpdated',
            ],
            'projectManagement' => [
                'fieldName' => 'tx_academicprojects_project_management',
            ],
            'contact' => [
                'fieldName' => 'tx_academicprojects_contact',
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
