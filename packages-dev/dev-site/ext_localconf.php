<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    // The icon overview of the development seed (ACE-594). Added after the
    // content rendering definitions rather than through a set or a static
    // template, because this is the one place that reaches both page trees of
    // an instance: the "/" tree is delivered through site sets and depends on
    // EXT:bootstrap_package, not on anything of this package, and the
    // "/legacy/" tree through a "sys_template" record. The committed site
    // configurations therefore need no change for the page to render.
    ExtensionManagementUtility::addTypoScript(
        'academics_dev_site',
        'setup',
        <<<'TYPOSCRIPT'
            tt_content.academicsdevsite_icons = FLUIDTEMPLATE
            tt_content.academicsdevsite_icons {
                templateName = IconOverview
                templateRootPaths.0 = EXT:academics_dev_site/Resources/Private/Templates/
                dataProcessing.10 = academics-dev-site-icon-overview
                dataProcessing.10.as = iconSections
            }
            TYPOSCRIPT,
        'defaultContentRendering',
    );
})();
