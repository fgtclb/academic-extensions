<?php

declare(strict_types=1);

namespace TESTS\BaseTestDependencyInjection\Service\ArrayObjectMapper;

use FGTCLB\AcademicBase\Service\ArrayObjectMapper;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapperTest;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Test service to ensure {@see ArrayObjectMapper} can be injected using inject-method injection,
 * {@see ArrayObjectMapperTest::canBeInjectMethodInjectedIntoPublicService()}.
 */
#[Autoconfigure(public: true)]
final class InjectMethodTestService
{
    #[Required]
    public ArrayObjectMapper $arrayToObjectMapper;

    public function injectSettingsObjectManager(ArrayObjectMapper $arrayToObjectMapper): void
    {
        $this->arrayToObjectMapper = $arrayToObjectMapper;
    }
}
