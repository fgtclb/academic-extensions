<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractAcademicBiteJobsTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-bite-jobs',
    ];
}
