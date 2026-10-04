<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Middleware\FrontendIconEndpoint;

return [
    'frontend' => [
        // The icon endpoint, <site base>/_academic/icons.json. After the site resolver,
        // which provides site, language and route tail, and after the maintenance mode;
        // before both authenticators, so it never starts a session or sends a cookie,
        // and before the page resolver, which would answer the path with a 404.
        //
        // Never reference "typo3/cms-frontend/base-redirect-resolver" or
        // "typo3/cms-frontend/static-route-resolver" here: EXT:redirects places its own
        // middleware between the authenticators and those two, and "after
        // base-redirect-resolver, before authentication" is a dependency cycle on TYPO3
        // v13 and v14 alike. "typo3/cms-frontend/tsfe" exists on TYPO3 v13 only.
        'fgtclb/academic-base/frontend-icon-endpoint' => [
            'target' => FrontendIconEndpoint::class,
            'after' => [
                'typo3/cms-frontend/site',
                'typo3/cms-frontend/maintenance-mode',
            ],
            'before' => [
                'typo3/cms-frontend/backend-user-authentication',
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
