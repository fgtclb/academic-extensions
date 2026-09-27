<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Controller;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Provides {@see self::dispatchModifyPluginViewEvent()} to the Extbase action controllers of
 * the academic extensions.
 *
 * Every path of an action that renders its view calls it once, after the action assigned its
 * own variables and before a variable a listener must not replace. It is deliberately not
 * hidden in an override of `htmlResponse()`: that would run after every assignment, the
 * protected ones included.
 *
 * Like every trait of the extensions, it reads no property and calls no method of the class
 * that uses it: the controller hands over what the event needs.
 *
 * @internal Only for the controllers of the academic extensions. A project listens to
 *           {@see ModifyPluginViewEvent} instead.
 */
trait DispatchModifyPluginViewEventMethodTrait
{
    /**
     * The context is built here, from the request and the settings as the action left them,
     * and not taken from the action: an action may change a setting after its query. Those
     * settings can differ from `{settings}` in the view, which Extbase assigns before the
     * action runs.
     *
     * It returns the dispatched event, so a caller can hand its context on, to another event
     * or to a service. A caller that needs nothing of it ignores the return value.
     *
     * @param array<string, mixed> $settings
     */
    protected function dispatchModifyPluginViewEvent(
        ServerRequestInterface $request,
        array $settings,
        FluidViewInterface|CoreViewInterface $view,
        EventDispatcherInterface $eventDispatcher,
    ): ModifyPluginViewEvent {
        /** @var ModifyPluginViewEvent $event */
        $event = $eventDispatcher->dispatch(new ModifyPluginViewEvent(
            pluginControllerActionContext: new PluginControllerActionContext(
                request: $request,
                settings: $settings,
            ),
            view: $view,
        ));
        return $event;
    }
}
