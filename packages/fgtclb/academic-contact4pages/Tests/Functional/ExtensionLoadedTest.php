<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicContacts4PagesTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'fgtclb/academic-contacts4pages',
        // extension keys
        'academic_base',
        'academic_persons',
        'academic_contacts4pages',
    ];
}
