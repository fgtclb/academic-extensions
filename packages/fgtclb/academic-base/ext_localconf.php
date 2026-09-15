<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Backend\FormDataProvider\KeepCurrentContentTypeSelectable;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems;

defined('TYPO3') or die;

(static function (): void {
    //==================================================================================================================
    // Keep a stored academic content type selectable where page TSconfig hides it
    //==================================================================================================================
    // Right after core resolved the select items, and before every provider that reads
    // the content type from the row. `TcaTtContentCtypeItemsRestrictionByBackendLayout`
    // exists from TYPO3 v14 on and has to see the restored value, so a backend layout
    // that disallows the type still gets core's own handling; the dependency ordering
    // ignores the entry on TYPO3 v13, where the class does not exist.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][KeepCurrentContentTypeSelectable::class] = [
        'depends' => [
            TcaSelectItems::class,
        ],
        'before' => [
            TcaSelectTreeItems::class,
            'TYPO3\\CMS\\Backend\\Form\\FormDataProvider\\TcaTtContentCtypeItemsRestrictionByBackendLayout',
        ],
    ];
})();
