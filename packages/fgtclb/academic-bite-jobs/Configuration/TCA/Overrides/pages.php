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
        'academic_bite_jobs',
        'Configuration/TSconfig/List/page.tsconfig',
        'Academic Bite Jobs: Job list',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_bite_jobs',
        'Configuration/TSconfig/Full/page.tsconfig',
        'Academic Bite Jobs: All components',
    );

})();
