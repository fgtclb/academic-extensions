<?php

declare(strict_types=1);

use FGTCLB\AcademicContacts4pages\Controller\ContactsController;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

    if ($versionInformation->getMajorVersion() < 12) {
        // Starting with TYPO3 v12.0 Configuration/page.tsconfig in an Extension is automatically loaded during build time
        // @see https://docs.typo3.org/m/typo3/reference-tsconfig/12.4/en-us/UsingSetting/PageTSconfig.html#pagesettingdefaultpagetsconfig
        ExtensionManagementUtility::addPageTSConfig('
            @import \'EXT:academic_contacts4pages/Configuration/page.tsconfig\'
        ');
    }

    ExtensionUtility::configurePlugin(
        'AcademicContacts4pages',
        'ContactsList',
        [
            ContactsController::class => 'list',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );
})();
