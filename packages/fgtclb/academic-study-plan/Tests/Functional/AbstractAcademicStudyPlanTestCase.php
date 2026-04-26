<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractAcademicStudyPlanTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
        'fgtclb/academic-study-plan',
    ];
}
