<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyPluginViewEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnTheConstructorValues(): void
    {
        $context = $this->createStub(PluginControllerActionContextInterface::class);
        $view = $this->createStub(ViewInterface::class);

        $event = new ModifyPluginViewEvent($context, $view);

        $this->assertSame($context, $event->getPluginControllerActionContext());
        $this->assertSame($view, $event->getView());
    }
}
