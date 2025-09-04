<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    // TYPO3 Backend - FormEngine
    'persons_edit_icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons_edit/Resources/Public/Icons/Plugin/persons_edit.svg',
    ],
    // Icons for frontend plugin rendering
    'academic-persons-edit-add-image' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons_edit/Resources/Public/Icons/Plugin/add-image.svg'
    ],
    'academic-persons-edit-add-item' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons_edit/Resources/Public/Icons/Plugin/add-item.svg'
    ],
    'academic-persons-edit-cancel' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons_edit/Resources/Public/Icons/Plugin/cancel.svg'
    ],
    'academic-persons-edit-delete' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons_edit/Resources/Public/Icons/Plugin/delete.svg'
    ],
    'academic-persons-edit-edit' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons_edit/Resources/Public/Icons/Plugin/edit.svg'
    ],
    'academic-persons-edit-save' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons_edit/Resources/Public/Icons/Plugin/save.svg'
    ],
    'academic-persons-edit-view' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons_edit/Resources/Public/Icons/Plugin/view.svg'
    ],
];
