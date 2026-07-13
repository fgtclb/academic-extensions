<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractAcademicBaseTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
    ];
}
