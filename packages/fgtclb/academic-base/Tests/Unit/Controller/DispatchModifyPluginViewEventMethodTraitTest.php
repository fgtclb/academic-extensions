<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Controller;

use FGTCLB\AcademicBase\Controller\DispatchModifyPluginViewEventMethodTrait;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DispatchModifyPluginViewEventMethodTraitTest extends UnitTestCase
{
    /**
     * The class using the trait has no property and no method of its own: the trait needs
     * nothing but its arguments. The event carries the context the action handed over, the
     * same object and not one built from it, so every event of a rendering shares it.
     */
    #[Test]
    public function dispatchesOneEventWithTheViewAndTheGivenContextAndReturnsIt(): void
    {
        $dispatcher = new class () implements EventDispatcherInterface {
            /** @var list<object> */
            public array $events = [];

            public function dispatch(object $event): object
            {
                $this->events[] = $event;
                return $event;
            }
        };
        $context = $this->createStub(PluginControllerActionContextInterface::class);
        $view = $this->createStub(ViewInterface::class);
        $controller = new class () {
            use DispatchModifyPluginViewEventMethodTrait {
                dispatchModifyPluginViewEvent as public;
            }
        };

        $returned = $controller->dispatchModifyPluginViewEvent($context, $view, $dispatcher);

        $this->assertCount(1, $dispatcher->events);
        $event = $dispatcher->events[0];
        $this->assertInstanceOf(ModifyPluginViewEvent::class, $event);
        $this->assertSame($event, $returned);
        $this->assertSame($view, $event->getView());
        $this->assertSame($context, $event->getPluginControllerActionContext());
    }
}
