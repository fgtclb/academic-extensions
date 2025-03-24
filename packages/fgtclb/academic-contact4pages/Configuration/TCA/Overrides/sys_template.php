<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {
    ExtensionManagementUtility::addStaticFile(
        'academic_contacts4pages',
        'Configuration/TypoScript/',
        'Contacts for Pages'
    );
})();
