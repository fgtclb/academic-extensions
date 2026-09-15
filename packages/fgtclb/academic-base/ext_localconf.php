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
    // the content type from the row: `TcaSelectTreeItems` is the only direct dependent
    // of `TcaSelectItems` on TYPO3 v12 and v13.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][KeepCurrentContentTypeSelectable::class] = [
        'depends' => [
            TcaSelectItems::class,
        ],
        'before' => [
            TcaSelectTreeItems::class,
        ],
    ];
})();
