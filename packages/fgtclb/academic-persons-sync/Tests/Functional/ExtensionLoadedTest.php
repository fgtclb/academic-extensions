<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsSync\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicPersonsSyncTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'fgtclb/academic-persons-edit',
        'fgtclb/academic-persons-sync',
        // extension keys
        'academic_base',
        'academic_persons',
        'academic_persons_edit',
        'academic_persons_sync',
    ];
}
