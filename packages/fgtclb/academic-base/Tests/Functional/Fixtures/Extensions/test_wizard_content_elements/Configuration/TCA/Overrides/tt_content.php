<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    // Three items in the `academic` group registered by academic_base, the shape every
    // academic extension registers its content elements in. Their order here is the
    // order the new content element wizard starts from.
    foreach (['first' => 'First', 'second' => 'Second', 'third' => 'Third'] as $key => $label) {
        ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'CType',
            [
                'label' => $label . ' academic element',
                'value' => 'testwizard_' . $key,
                'icon' => 'content-text',
                'group' => 'academic',
            ],
        );
        $GLOBALS['TCA']['tt_content']['types']['testwizard_' . $key] = [
            'showitem' => '--palette--;;general, header',
        ];
    }

    // One element in the group core lists after "Special elements", so the wizard shows a
    // group behind the one the academic group is placed after, and one in "Form elements",
    // whose identifier sorts between those of other core groups.
    foreach (['plugins' => 'Plugin', 'forms' => 'Form'] as $group => $label) {
        ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'CType',
            [
                'label' => $label . ' element',
                'value' => 'testwizard_' . $group,
                'icon' => 'content-' . ($group === 'plugins' ? 'plugin' : 'form'),
                'group' => $group,
            ],
        );
        $GLOBALS['TCA']['tt_content']['types']['testwizard_' . $group] = [
            'showitem' => '--palette--;;general, header',
        ];
    }
})();
