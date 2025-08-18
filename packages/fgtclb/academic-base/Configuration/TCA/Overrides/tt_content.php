<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {

    //==================================================================================================================
    // Add content element group `academic` shared across all academic extensions
    //==================================================================================================================
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academic',
        'LLL:EXT:academic_base/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label',
    );

})();
