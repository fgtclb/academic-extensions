<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractAcademicContacts4PagesTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-persons',
        'fgtclb/academic-contacts4pages',
    ];
}
