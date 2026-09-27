<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Event;

use FGTCLB\AcademicBase\Controller\DispatchModifyPluginViewEventMethodTrait;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Dispatched once each time an action of a plugin of academic_bite_jobs,
 * academic_contacts4pages, academic_jobs, academic_partners, academic_persons,
 * academic_programs or academic_projects renders its view, after the action assigned its own
 * variables, so a listener can assign further ones. One event for all of them: a listener that
 * needs one plugin checks the plugin and action name of the context.
 *
 * An action may assign a variable after the event on purpose, so a listener cannot replace it;
 * the validations of the job form are the one case today.
 *
 * Dispatched through {@see DispatchModifyPluginViewEventMethodTrait}.
 *
 * @api
 */
final class ModifyPluginViewEvent
{
    public function __construct(
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
        private readonly FluidViewInterface|CoreViewInterface $view,
    ) {}

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }

    /**
     * The view the action renders. It is an object, so a variable assigned to it reaches the
     * templates without being handed back.
     */
    public function getView(): FluidViewInterface|CoreViewInterface
    {
        return $this->view;
    }
}
