<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

(static function (): void {

    //==================================================================================================================
    // Static TypoScript templates, selectable in a "sys_template" record for installations that do not use site sets.
    //
    // The registered folders are the same ones the sets of this extension deliver through their "typoscript" key.
    // Use one mechanism per site, not both - see the extension documentation, chapter "Configuration".
    //==================================================================================================================
    ExtensionManagementUtility::addStaticFile(
        'academic_study_plan',
        'Configuration/TypoScript/ContentElement',
        'Academic Study Plan: Content element',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_study_plan',
        'Configuration/TypoScript/Full',
        'Academic Study Plan: All components',
    );

})();
