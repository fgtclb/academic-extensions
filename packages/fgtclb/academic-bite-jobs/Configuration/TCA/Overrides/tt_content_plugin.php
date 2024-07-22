<?php

declare(strict_types=1);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'AcademicBiteJobs',
    'List',
    'Academic Bite Jobs: List',
    'EXT:academic_bite_jobs/Resources/Public/Icons/jobs_bite_icon.svg'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicbitejobs_list'] = 'recursive,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicbitejobs_list'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'academicbitejobs_list',
    'FILE:EXT:academic_bite_jobs/Configuration/FlexForms/AcademicBiteJobsList.xml'
);
