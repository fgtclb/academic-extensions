<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $ll = static fn (string $key): string => sprintf('LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_be.xlf:%s', $key);

    ExtensionUtility::registerPlugin(
        'AcademicContacts4pages',
        'ContactsList',
        $ll('plugin.contacts_list.title'),
        'EXT:academic_contacts4pages/Resources/Public/Icons/Extension.svg',
        'plugins',
        $ll('plugin.contacts_list.description'),
    );
})();
