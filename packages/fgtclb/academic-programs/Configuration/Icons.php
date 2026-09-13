<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The icons of the academic program page type and the two program content elements:
 * Font Awesome Free solid, drawn in `currentColor` and inlined by the provider of
 * EXT:academic_base, so they follow the text colour in both backend colour schemes
 * instead of keeping the ink of an <img>. Licence and origin of every file:
 * Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-<extkey>-<group>-<name>`, files `Icons/<group>/<name>.svg`.
 * The page type and the content elements share one drawing under two identifiers,
 * so a project can replace either of them on its own. The category type icons are
 * registered from Configuration/CategoryTypes.yaml, not here.
 */
return [
    'tx-academicprograms-doktype-program' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_programs/Resources/Public/Icons/plugin/programs.svg',
    ],
    'tx-academicprograms-plugin-programs' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_programs/Resources/Public/Icons/plugin/programs.svg',
    ],
];
