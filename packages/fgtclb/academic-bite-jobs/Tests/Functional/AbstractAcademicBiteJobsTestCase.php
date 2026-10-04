<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional;

use FGTCLB\TestingHelper\TestCase\FunctionalTestCase;

abstract class AbstractAcademicBiteJobsTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-bite-jobs',
    ];
}
