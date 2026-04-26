<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicBiteJobsTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/academic-bite-jobs',
        // extension keys
        'academic_base',
        'academic_bite_jobs',
    ];
}
