<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {

    $typo3MajorVersion = (new Typo3Version())->getMajorVersion();

    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academic',
        'LLL:EXT:academic_bite_jobs/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label',
    );

    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_bite_jobs/Resources/Private/Language/locallang_be.xlf:plugin.list.label',
            'value' => 'academicbitejobs_list',
            'icon' => 'bitejobs_list',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_bite_jobs'
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        'academicbitejobs_list',
        sprintf('FILE:EXT:academic_bite_jobs/Configuration/FlexForms/Core%s/AcademicBiteJobsList.xml', $typo3MajorVersion)
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicbitejobs_list'] = 'recursive,select_key';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicbitejobs_list'] = 'pi_flexform';

})();
