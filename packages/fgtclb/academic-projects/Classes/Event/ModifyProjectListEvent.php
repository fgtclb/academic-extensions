<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicProjects\Controller\ProjectController;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Domain\Model\Project;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Dispatched in {@see ProjectController::listAction()} after the query and before the view
 * variables are assigned. A listener replaces the projects, replaces the applicable
 * categories, or assigns variables of its own to the view.
 *
 * The categories are the ones computed from the queried projects. They are not recomputed
 * after the event, so a listener that replaces the projects and wants the filter to match
 * them sets the categories as well.
 */
final class ModifyProjectListEvent
{
    /**
     * @param QueryResultInterface<int, Project> $projects
     */
    public function __construct(
        private QueryResultInterface $projects,
        private CategoryCollection $categories,
        private readonly ProjectDemand $demand,
        private readonly FluidViewInterface|CoreViewInterface $view,
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
    ) {}

    /**
     * @return QueryResultInterface<int, Project>
     */
    public function getProjects(): QueryResultInterface
    {
        return $this->projects;
    }

    /**
     * @param QueryResultInterface<int, Project> $projects
     */
    public function setProjects(QueryResultInterface $projects): void
    {
        $this->projects = $projects;
    }

    public function getCategories(): CategoryCollection
    {
        return $this->categories;
    }

    public function setCategories(CategoryCollection $categories): void
    {
        $this->categories = $categories;
    }

    public function getDemand(): ProjectDemand
    {
        return $this->demand;
    }

    public function getView(): FluidViewInterface|CoreViewInterface
    {
        return $this->view;
    }

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
