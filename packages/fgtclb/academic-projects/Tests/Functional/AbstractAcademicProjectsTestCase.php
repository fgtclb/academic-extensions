<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractAcademicProjectsTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/category-types',
        'fgtclb/academic-projects',
    ];
}
