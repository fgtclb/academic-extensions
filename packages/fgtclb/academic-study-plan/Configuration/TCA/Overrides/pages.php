<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_study_plan',
        'Configuration/TsConfig/Default.tsconfig',
        'Academic StudyPlan (Default)'
    );

})();
