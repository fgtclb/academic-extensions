<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicBaseTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        // extension keys
        'academic_base',
    ];
}
