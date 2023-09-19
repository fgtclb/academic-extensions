<?php

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '@import \'EXT:academic_bite_jobs/Configuration/TsConfig/All.tsconfig\''
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'AcademicBiteJobs',
        'List',
        [
            \FGTCLB\AcademicBiteJobs\Controller\BiteJobsController::class => 'list',
        ]
    );
})();
