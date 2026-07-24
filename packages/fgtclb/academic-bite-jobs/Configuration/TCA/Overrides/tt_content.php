<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {

    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_bite_jobs/Resources/Private/Language/locallang_be.xlf:plugin.bite.list.label',
            'value' => 'academicbitejobs_list',
            'icon' => 'bitejobs_list',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_bite_jobs'
    );

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_bite_jobs/Configuration/FlexForms/AcademicBiteJobsList.xml',
        'academicbitejobs_list'
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_bite_jobs/Resources/Private/Language/locallang_be.xlf:plugin.bite.list.configuration',
            'pi_flexform',
            'pages',
        ]),
        'academicbitejobs_list',
        'after:subheader',
    );
})();
