<?php

(static function (): void {
    $projectDokType = \FGTCLB\AcademicProjects\Domain\Enumeration\Page::TYPE_EDUCATIONAL_PROJECT;

    $GLOBALS['PAGES_TYPES'][$projectDokType] = [
        'type' => 'web',
        'allowedTables' => '*',
    ];
})();
