<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {

    ExtensionManagementUtility::addStaticFile(
        'academic_study_plan',
        'Configuration/TypoScript/Default',
        'Academic StudyPlan (Default)'
    );

})();
