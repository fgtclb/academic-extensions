<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The icons of the backend icon registry. The frontend renders no icon of this
 * extension, so there is no Configuration/FrontendIcons.php.
 *
 * The icon of the content element and its new content element wizard entry: Font
 * Awesome Free solid, drawn in `currentColor` and inlined by the provider of
 * EXT:academic_base, so it takes the colour of the surrounding text in both backend
 * colour schemes. Licence and origin: Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-<extension key without underscores>-<group>-<name>`, files
 * `Icons/<group>/<name>.svg`.
 */
return [
    'tx-academicbitejobs-plugin-bite-jobs' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_bite_jobs/Resources/Public/Icons/plugin/bite-jobs.svg',
    ],
];
