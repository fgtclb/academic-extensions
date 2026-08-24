<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    // Registered, and not only shipped. The "/legacy/" tree names this folder in
    // the "include_static_file" of its root "sys_template" record, and a path
    // that is not offered here is a path an integrator could not have selected -
    // which is half of what "DeliveryRegistrationTest" checks.
    ExtensionManagementUtility::addStaticFile(
        'academics_dev_site',
        'Configuration/TypoScript',
        'Academics development site page object',
    );
})();
