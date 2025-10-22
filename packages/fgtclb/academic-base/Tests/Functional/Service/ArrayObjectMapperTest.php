<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service;

use FGTCLB\AcademicBase\Service\ArrayObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use TESTS\BaseTestDependencyInjection\Service\ArrayObjectMapper\ConstructorInjectionTestService;
use TESTS\BaseTestDependencyInjection\Service\ArrayObjectMapper\InjectMethodTestService;

final class ArrayObjectMapperTest extends AbstractArrayObjectMapperTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad = array_unique(
            [
                ...array_values($this->testExtensionsToLoad),
                ...array_values([
                    'tests/base-test-dependency-injection',
                ]),
            ],
        );
        parent::setUp();
    }

    #[Test]
    public function serviceCanBePulledFromDependencyContainer(): void
    {
        $this->createSubject();
    }

    #[Test]
    public function serviceCanBeCreatedWithGeneralUtilityMakeInstance(): void
    {
        $this->createSubjectWithGeneralUtility();
    }

    #[Test]
    public function canBeConstructorInjectedIntoPublicService(): void
    {
        $service = $this->get(ConstructorInjectionTestService::class);
        $this->assertInstanceOf(ConstructorInjectionTestService::class, $service);
        $this->assertInstanceOf(ArrayObjectMapper::class, $service->arrayToObjectMapper);
    }

    #[Test]
    public function canBeInjectMethodInjectedIntoPublicService(): void
    {
        $service = $this->get(InjectMethodTestService::class);
        $this->assertInstanceOf(InjectMethodTestService::class, $service);
        $this->assertInstanceOf(ArrayObjectMapper::class, $service->arrayToObjectMapper);
    }
}
