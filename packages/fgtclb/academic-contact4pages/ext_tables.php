<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

$versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

// The usage of method allowTableOnStandardPages() is deprecated with v12.0 and replaced with TCA setting.
// @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/FileStructure/ExtTables.html#extension-configuration-files-allow-table-standard
if ($versionInformation->getMajorVersion() < 12) {
    ExtensionManagementUtility::allowTableOnStandardPages(
        'tx_academiccontacts4pages_domain_model_contact',
    );
}
