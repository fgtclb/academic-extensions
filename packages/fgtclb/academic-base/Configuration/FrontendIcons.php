<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/**
 * The icons of the frontend icon registry, see the configuration chapter of the
 * manual.
 *
 * `default-not-found` is the placeholder an unknown identifier renders, the drawing
 * TYPO3 shows for an unknown icon. A site package that depends on academic_base
 * replaces it by registering `default-not-found` in its own file.
 *
 * The other icons are shared by the academic extensions: Font Awesome Free solid, drawn
 * in `currentColor` and inlined by the provider of this extension, so they take the
 * colour of the surrounding text. Licence and origin of every file:
 * Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-<extension key without underscores>-<group>-<name>`, files
 * `Icons/<group>/<name>.svg`: `action` for something a control does, `state` for a
 * state a control shows, `info` for the glyph in front of a piece of information.
 * The group decides the registry: these three groups are frontend icons and are
 * registered here only, a `record`, `plugin` or `doktype` icon belongs in
 * Configuration/Icons.php. A project replaces one of them everywhere by registering
 * the same identifier in the Configuration/FrontendIcons.php of a package that
 * depends on this one, which is read later. Documentation/Icons/Index.rst lists the
 * set.
 */
return [
    'default-not-found' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/default/default-not-found.svg',
    ],
    'tx-academicbase-action-add' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/add.svg',
    ],
    'tx-academicbase-action-back' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/back.svg',
    ],
    'tx-academicbase-action-clear' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/clear.svg',
    ],
    'tx-academicbase-action-close' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/close.svg',
    ],
    'tx-academicbase-action-collapse' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/collapse.svg',
    ],
    'tx-academicbase-action-delete' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/delete.svg',
    ],
    'tx-academicbase-action-drag' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/drag.svg',
    ],
    'tx-academicbase-action-edit' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/edit.svg',
    ],
    'tx-academicbase-action-expand' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/expand.svg',
    ],
    'tx-academicbase-action-help' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/help.svg',
    ],
    'tx-academicbase-action-move-down' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/move-down.svg',
    ],
    'tx-academicbase-action-move-up' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/move-up.svg',
    ],
    'tx-academicbase-action-save' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/save.svg',
    ],
    'tx-academicbase-action-undo' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/undo.svg',
    ],
    'tx-academicbase-action-upload-image' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/upload-image.svg',
    ],
    'tx-academicbase-action-view-close' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/view-close.svg',
    ],
    'tx-academicbase-action-view' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/action/view.svg',
    ],
    'tx-academicbase-state-hidden' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/state/hidden.svg',
    ],
    'tx-academicbase-state-visible' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/state/visible.svg',
    ],
    'tx-academicbase-info-calendar' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/calendar.svg',
    ],
    'tx-academicbase-info-company' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/company.svg',
    ],
    'tx-academicbase-info-contract' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/contract.svg',
    ],
    'tx-academicbase-info-degree' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/degree.svg',
    ],
    'tx-academicbase-info-department' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/department.svg',
    ],
    'tx-academicbase-info-email' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/email.svg',
    ],
    'tx-academicbase-info-employment' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/employment.svg',
    ],
    'tx-academicbase-info-information' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/information.svg',
    ],
    'tx-academicbase-info-international' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/international.svg',
    ],
    'tx-academicbase-info-link' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/link.svg',
    ],
    'tx-academicbase-info-location' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/location.svg',
    ],
    'tx-academicbase-info-partnership' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/partnership.svg',
    ],
    'tx-academicbase-info-person' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/person.svg',
    ],
    'tx-academicbase-info-phone' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/phone.svg',
    ],
    'tx-academicbase-info-recommendation' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/recommendation.svg',
    ],
    'tx-academicbase-info-role' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/role.svg',
    ],
    'tx-academicbase-info-room' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/room.svg',
    ],
    'tx-academicbase-info-sector' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/sector.svg',
    ],
    'tx-academicbase-info-time' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/time.svg',
    ],
];
