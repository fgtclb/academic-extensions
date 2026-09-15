<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    // The shape every academic extension registers its content types in: an
    // item in the `academic` group registered by academic_base.
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'Hidden academic element',
            'value' => 'testhidden_academic',
            'icon' => 'content-text',
            'group' => 'academic',
        ],
    );
    $GLOBALS['TCA']['tt_content']['types']['testhidden_academic'] = [
        'showitem' => '--palette--;;general, header',
    ];

    // The same, outside the `academic` group: a type of any other extension.
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'Hidden other element',
            'value' => 'testhidden_other',
            'icon' => 'content-text',
            'group' => 'default',
        ],
    );
    $GLOBALS['TCA']['tt_content']['types']['testhidden_other'] = [
        'showitem' => '--palette--;;general, header',
    ];
})();
