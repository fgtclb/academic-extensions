<?php

declare(strict_types=1);

/**
 * Import map of the frontend modules of this extension.
 */
return [
    'dependencies' => [
        'core',
    ],
    'imports' => [
        '@fgtclb/academic-persons/frontend/' => 'EXT:academic_persons/Resources/Public/JavaScript/frontend/',
    ],
];
