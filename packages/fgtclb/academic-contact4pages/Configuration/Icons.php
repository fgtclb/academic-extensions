<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The icons of this extension: Font Awesome Free solid, drawn in `currentColor` and
 * inlined by the provider of EXT:academic_base, so they take the colour of the
 * surrounding text in both backend colour schemes. Licence and origin of the file this
 * extension ships: Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-<extkey>-<group>-<name>`, files `Icons/<group>/<name>.svg`:
 * `plugin` for the content element (TCA and new content element wizard), `record` for
 * the TCA record types. The content element and the contact record share one drawing;
 * the role record uses the shared role glyph of EXT:academic_base. A project replaces
 * one of them by registering the same identifier in its own Configuration/Icons.php.
 */
return [
    'tx-academiccontacts4pages-plugin-contacts' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/plugin/contacts.svg',
    ],
    'tx-academiccontacts4pages-record-contact' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_contacts4pages/Resources/Public/Icons/plugin/contacts.svg',
    ],
    'tx-academiccontacts4pages-record-role' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/role.svg',
    ],
];
