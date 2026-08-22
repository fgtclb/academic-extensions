<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

(static function (): void {

    //==================================================================================================================
    // Page TSconfig, selectable in the page field "Page TSconfig" for installations that do not use site sets.
    //
    // The files are the same ones the sets of this extension deliver. Use one mechanism per site, not both.
    //==================================================================================================================
    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_base',
        'Configuration/TSconfig/CTypeGroup/page.tsconfig',
        'Academic Base: Content element group',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_base',
        'Configuration/TSconfig/Full/page.tsconfig',
        'Academic Base: All components',
    );

})();
