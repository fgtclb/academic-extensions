<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Fgtclb\AcademicPersonsEdit\Controller\ContractController;
use Fgtclb\AcademicPersonsEdit\Controller\ProfileController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    ExtensionUtility::configurePlugin(
        'AcademicPersonsEdit',
        'ProfileEditing',
        [
            ProfileController::class => implode(',', [
                'list',
                'show',
                'edit',
                'update',
                'addProfileImage',
                'removeProfileImage',
                'addProfileInformation',
                'createProfileInformation',
                'editProfileInformation',
                'updateProfileInformation',
                'confirmDeleteProfileInformation',
                'deleteProfileInformation',
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
                'sortContracts',
            ]),
        ],
        [
            ProfileController::class => implode(',', [
                'list',
                'show',
                'edit',
                'update',
                'addProfileImage',
                'removeProfileImage',
                'addProfileInformation',
                'createProfileInformation',
                'editProfileInformation',
                'updateProfileInformation',
                'confirmDeleteProfileInformation',
                'deleteProfileInformation',
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
                'sortContracts',
            ]),
        ],
    );
})();
