<?php

declare(strict_types=1);

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'academic_contacts4pages',
        'Configuration/TypoScript/',
        'Contacts for Pages'
    );
})();
