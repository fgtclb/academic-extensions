<?php

declare(strict_types=1);

use FGTCLB\AcademicContacts4pages\Controller\ContactsController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    ExtensionUtility::configurePlugin(
        'AcademicContacts4pages',
        'List',
        [
            ContactsController::class => 'list',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );
})();
