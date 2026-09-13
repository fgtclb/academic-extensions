<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The icons of this extension: Font Awesome Free solid, drawn in `currentColor` and
 * inlined by the provider of EXT:academic_base, so they take the colour of the
 * surrounding text in both backend colour schemes. Licence and origin of every file:
 * Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-<extkey>-<group>-<name>`, files `Icons/<group>/<name>.svg`:
 * `plugin` for the content elements and the new content element wizard, `record` for
 * the job table. Both show the same drawing. The glyphs in front of the job properties
 * in the frontend are the shared `tx-academicbase-info-*` icons of EXT:academic_base,
 * mapped in Resources/Private/Partials/Job/PropertyIcon.html.
 */
return [
    'tx-academicjobs-plugin-jobs' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/plugin/jobs.svg',
    ],
    'tx-academicjobs-record-job' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/plugin/jobs.svg',
    ],
];
