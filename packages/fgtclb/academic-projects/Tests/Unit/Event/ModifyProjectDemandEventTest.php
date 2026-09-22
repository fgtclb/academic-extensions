<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyProjectDemandEventTest extends UnitTestCase
{
    #[Test]
    public function getDemandReturnsInstanceSetInConstructor(): void
    {
        $demand = new ProjectDemand();
        $event = new ModifyProjectDemandEvent($demand, $this->createStub(PluginControllerActionContextInterface::class));
        $this->assertSame($demand, $event->getDemand());
    }

    #[Test]
    public function getDemandReturnsTheDemandAListenerReplacedItWith(): void
    {
        $replacement = new ProjectDemand();
        $event = new ModifyProjectDemandEvent(
            new ProjectDemand(),
            $this->createStub(PluginControllerActionContextInterface::class),
        );
        $event->setDemand($replacement);
        $this->assertSame($replacement, $event->getDemand());
    }

    #[Test]
    public function getPluginControllerActionContextReturnsInstanceSetInConstructor(): void
    {
        $context = $this->createStub(PluginControllerActionContextInterface::class);
        $event = new ModifyProjectDemandEvent(new ProjectDemand(), $context);
        $this->assertSame($context, $event->getPluginControllerActionContext());
    }
}
