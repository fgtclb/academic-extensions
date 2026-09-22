<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicProjects\Controller\ProjectController;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;

/**
 * Dispatched in {@see ProjectController::listAction()}, which serves both project list
 * plugins, after the demand has been built from the content element settings and the
 * request and before the projects are queried. The demand a listener hands back is the one
 * that is queried and assigned to the view.
 *
 * The plugin the demand belongs to is on the context: the two list plugins are registered
 * as `ProjectList` and `ProjectListSingle`.
 */
final class ModifyProjectDemandEvent
{
    public function __construct(
        private ProjectDemand $demand,
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
    ) {}

    public function getDemand(): ProjectDemand
    {
        return $this->demand;
    }

    public function setDemand(ProjectDemand $demand): void
    {
        $this->demand = $demand;
    }

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
