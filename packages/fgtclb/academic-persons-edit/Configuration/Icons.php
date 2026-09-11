<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/*
 * The icon of the profile editing content element: Font Awesome Free solid, drawn in
 * `currentColor` and inlined by the provider of EXT:academic_base, so it follows the
 * backend colour scheme in the page module and the new content element wizard.
 * Licence and origin: Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * The action and state icons of the editing frontend are not registered here: the
 * templates render the shared `tx-academicbase-action-*` and `tx-academicbase-state-*`
 * identifiers of EXT:academic_base, which a project replaces for every academic
 * extension at once by registering the same identifier in its own Configuration/Icons.php.
 */
return [
    'tx-academicpersonsedit-plugin-profile-editing' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons_edit/Resources/Public/Icons/plugin/profile-editing.svg',
    ],
];
