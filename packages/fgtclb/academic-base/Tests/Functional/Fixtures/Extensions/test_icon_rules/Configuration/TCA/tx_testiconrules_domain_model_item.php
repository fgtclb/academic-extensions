<?php

return [
    'ctrl' => [
        'title' => 'tx_testiconrules_domain_model_item',
        'label' => 'title',
        'typeicon_classes' => [
            'default' => 'tx-testiconrules-record-item',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'title'],
    ],
    'columns' => [
        'title' => [
            'label' => 'title',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
];
