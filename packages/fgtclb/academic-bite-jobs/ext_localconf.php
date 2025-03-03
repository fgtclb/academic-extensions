<?php

(static function (): void {

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'AcademicBiteJobs',
        'List',
        [
            \FGTCLB\AcademicBiteJobs\Controller\BiteJobsController::class => 'list',
        ]
    );
})();
