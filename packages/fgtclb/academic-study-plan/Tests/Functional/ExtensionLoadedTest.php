<?php

namespace FGTCLB\AcademicStudyPlan\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicStudyPlanTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/academic-study-plan',
        // extension keys
        'academic_base',
        'academic_study_plan',
    ];
}
