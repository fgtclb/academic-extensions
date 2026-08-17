<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// The instance TypoScript as a classic static template.
//
// Both development instances are wired through one root "sys_template" record
// rather than through site sets, because site sets arrived in TYPO3 v13.1
// (Feature: #103437) and this branch also supports v12. Every set the academic
// extensions ship is a plain "@import" of the very same files their static
// templates point at, so the two mechanisms deliver identical TypoScript - and
// one mechanism that works on both versions is worth more here than two that
// each work on one.
//
// Registering it makes the include selectable in the backend, next to the ones
// of the extensions. The seed writes the same path into "include_static_file"
// directly.
ExtensionManagementUtility::addStaticFile(
    'academics_dev_site',
    'Configuration/TypoScript',
    'Academics Development Site',
);
