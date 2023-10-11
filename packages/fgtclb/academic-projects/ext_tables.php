<?php

(static function (): void {
    $projectDokType = \FGTCLB\AcademicProjects\Domain\Enumeration\Page::TYPE_ACEDEMIC_PROJECT;

    $GLOBALS['PAGES_TYPES'][$projectDokType] = [
        'type' => 'web',
        'allowedTables' => '*',
    ];
})();
