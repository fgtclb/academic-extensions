<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional;

use FGTCLB\TestingHelper\TestCase\FunctionalTestCase;

abstract class AbstractAcademicStudyPlanTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-study-plan',
    ];
}
