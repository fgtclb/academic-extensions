<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The icons of the project page type and the two project content elements: Font
 * Awesome Free solid, drawn in `currentColor` and inlined by the provider of
 * EXT:academic_base, so they take the colour of the surrounding text in both backend
 * colour schemes. Licence and origin: Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-<extkey>-<group>-<name>`, files `Icons/<group>/<name>.svg`.
 * The page type and the content elements share one drawing, but not one identifier,
 * so a project can replace either of them on its own by registering the same
 * identifier in its own Configuration/Icons.php.
 *
 * The category type icons are not registered here: EXT:category_types registers them
 * from Configuration/CategoryTypes.yaml.
 */
return [
    'tx-academicprojects-doktype-project' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_projects/Resources/Public/Icons/plugin/projects.svg',
    ],
    'tx-academicprojects-plugin-projects' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_projects/Resources/Public/Icons/plugin/projects.svg',
    ],
];
