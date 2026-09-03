<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use FGTCLB\AcademicPersonsEdit\Controller\ContractController;
use FGTCLB\AcademicPersonsEdit\Controller\EmailAddressController;
use FGTCLB\AcademicPersonsEdit\Controller\InlineProfileController;
use FGTCLB\AcademicPersonsEdit\Controller\PhoneNumberController;
use FGTCLB\AcademicPersonsEdit\Controller\PhysicalAddressController;
use FGTCLB\AcademicPersonsEdit\Controller\ProfileController;
use FGTCLB\AcademicPersonsEdit\Controller\ProfileInformationController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    // Temporary legacy compatibility registration. Keep existing ProfileEditing
    // records and reference tests working, but do not use these controllers from
    // InlineProfile or expose this CType for new backend content elements.
    ExtensionUtility::configurePlugin(
        'AcademicPersonsEdit',
        'ProfileEditing',
        [
            ProfileController::class => implode(',', [
                'list',
                'show',
                'edit',
                'update',
                'editImage',
                'addImage',
                'removeImage',
                'toggleSkipSync',
            ]),
            ProfileInformationController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
            ]),
            ContractController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
            ]),
            PhysicalAddressController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
                'toggleVisibility',
            ]),
            EmailAddressController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
                'toggleVisibility',
            ]),
            PhoneNumberController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
                'toggleVisibility',
            ]),
        ],
        [
            ProfileController::class => implode(',', [
                'list',
                'show',
                'edit',
                'update',
                'editImage',
                'addImage',
                'removeImage',
                'toggleSkipSync',
            ]),
            ProfileInformationController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
            ]),
            ContractController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
            ]),
            PhysicalAddressController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
                'toggleVisibility',
            ]),
            EmailAddressController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
                'toggleVisibility',
            ]),
            PhoneNumberController::class => implode(',', [
                'list',
                'show',
                'new',
                'create',
                'edit',
                'update',
                'confirmDelete',
                'delete',
                'sort',
                'toggleVisibility',
            ]),
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
    ExtensionUtility::configurePlugin(
        'AcademicPersonsEdit',
        'InlineProfile',
        [
            InlineProfileController::class => implode(',', [
                'list',
                'index',
                'update',
                'updateSkipSync',
                'uploadImage',
                'deleteImage',
                'documentForm',
                'createDocument',
                'updateDocument',
                'deleteDocument',
                'sortDocument',
                'contractContactForm',
                'createContractContact',
                'updateContractContact',
                'deleteContractContact',
                'sortContractContact',
            ]),
        ],
        [
            InlineProfileController::class => implode(',', [
                'list',
                'index',
                'update',
                'updateSkipSync',
                'uploadImage',
                'deleteImage',
                'documentForm',
                'createDocument',
                'updateDocument',
                'deleteDocument',
                'sortDocument',
                'contractContactForm',
                'createContractContact',
                'updateContractContact',
                'deleteContractContact',
                'sortContractContact',
            ]),
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
})();
