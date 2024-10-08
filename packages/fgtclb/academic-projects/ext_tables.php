<?php

use FGTCLB\AcademicProjects\Enumeration\PageTypes;

(static function (): void {
    $projectDokType = PageTypes::TYPE_ACEDEMIC_PROJECT;

    $GLOBALS['PAGES_TYPES'][$projectDokType] = [
        'type' => 'web',
        'allowedTables' => '*',
    ];
})();
