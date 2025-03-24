<?php

use FGTCLB\AcademicBiteJobs\Controller\BiteJobsController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    ExtensionUtility::configurePlugin(
        'AcademicBiteJobs',
        'List',
        [
            BiteJobsController::class => 'list',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
})();
