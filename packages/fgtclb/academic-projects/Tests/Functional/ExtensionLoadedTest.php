<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicProjectsTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/academic-projects',
        // extension keys
        'academic_base',
        'academic_projects',
    ];
}
