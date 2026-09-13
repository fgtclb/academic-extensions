<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    // The content element of the icon overview page of the seed (ACE-594). It
    // has nothing to configure: the list is read from the icon registry when it
    // renders. Registered rather than only rendered, so the page module and the
    // record editor show a type instead of an unknown value.
    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'Icons of the academic extensions (development seed)',
        'value' => 'academicsdevsite_icons',
        'group' => 'special',
    ]);
    // The fields of a plain header element, whatever the core version calls
    // their tabs and palettes.
    $GLOBALS['TCA']['tt_content']['types']['academicsdevsite_icons'] = $GLOBALS['TCA']['tt_content']['types']['header'];
})();
