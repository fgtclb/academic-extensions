<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

ExtensionManagementUtility::addStaticFile(
    'academic_bite_jobs',
    'Configuration/TypoScript',
    'Academic Bite Jobs'
);
