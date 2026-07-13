<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Environment;

use FGTCLB\AcademicBase\Core13\Environment\FrontendEnvironmentBuilder as Core13FrontendEnvironmentBuilder;
use FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactory;
use FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactoryInterface;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class EnvironmentBuilderFactoryTest extends AbstractAcademicBaseTestCase
{
    #[Test]
    public function publicServiceCanBeInstantiatedBasedOnInterface(): void
    {
        $factory = GeneralUtility::makeInstance(EnvironmentBuilderFactoryInterface::class);
        $this->assertInstanceOf(EnvironmentBuilderFactoryInterface::class, $factory);
        $this->assertInstanceOf(EnvironmentBuilderFactory::class, $factory);
    }

    #[Test]
    public function publicServiceCanBeInstantiatedBasedOnClassName(): void
    {
        $factory = GeneralUtility::makeInstance(EnvironmentBuilderFactory::class);
        $this->assertInstanceOf(EnvironmentBuilderFactoryInterface::class, $factory);
        $this->assertInstanceOf(EnvironmentBuilderFactory::class, $factory);
    }

    #[Test]
    public function createReturnsTypoV13FrontendEnvironmentBuilderInstance(): void
    {
        $stateBuildContext = new StateBuildContext(
            applicationType: ApplicationType::FRONTEND,
            pageId: null,
            languageId: null,
        );
        $builder = GeneralUtility::makeInstance(EnvironmentBuilderFactory::class)->create($stateBuildContext);
        $this->assertInstanceOf(Core13FrontendEnvironmentBuilder::class, $builder);
    }

    #[Test]
    public function createThrowsExceptionForBackendEnvironmentBuilderInstance(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1762256802);
        $stateBuildContext = new StateBuildContext(
            applicationType: ApplicationType::BACKEND,
            pageId: null,
            languageId: null,
        );
        GeneralUtility::makeInstance(EnvironmentBuilderFactory::class)->create($stateBuildContext);
    }
}
