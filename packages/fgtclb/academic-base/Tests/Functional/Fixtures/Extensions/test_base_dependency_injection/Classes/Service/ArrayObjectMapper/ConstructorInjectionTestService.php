<?php

declare(strict_types=1);

namespace TESTS\BaseTestDependencyInjection\Service\ArrayObjectMapper;

use FGTCLB\AcademicBase\Service\ArrayObjectMapper;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapperTest;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Test service to ensure that {@see ArrayObjectMapper} can be constructor injected,
 * {@see ArrayObjectMapperTest::canBeConstructorInjectedIntoPublicService()}.
 */
#[Autoconfigure(public: true)]
final class ConstructorInjectionTestService
{
    public function __construct(
        public readonly ArrayObjectMapper $arrayToObjectMapper,
    ) {}
}
