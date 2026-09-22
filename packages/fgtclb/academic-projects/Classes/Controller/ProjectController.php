<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Controller;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicProjects\Domain\Repository\ProjectRepository;
use FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent;
use FGTCLB\AcademicProjects\Event\ModifyProjectListEvent;
use FGTCLB\AcademicProjects\Factory\DemandFactory;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ProjectController extends ActionController
{
    public function __construct(
        protected readonly ProjectRepository $projectRepository,
        protected readonly CategoryRepository $categoryRepository,
        protected readonly DemandFactory $demandFactory
    ) {}

    /**
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function listAction(?array $demand = null): ResponseInterface
    {
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $demandObject = $this->demandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        $context = $this->pluginControllerActionContext();
        /** @var ModifyProjectDemandEvent $demandEvent */
        $demandEvent = $this->eventDispatcher->dispatch(new ModifyProjectDemandEvent($demandObject, $context));
        $demandObject = $demandEvent->getDemand();

        $projects = $this->projectRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('projects', ...array_values($projects->toArray()));

        /** @var ModifyProjectListEvent $listEvent */
        $listEvent = $this->eventDispatcher->dispatch(new ModifyProjectListEvent(
            projects: $projects,
            categories: $categories,
            demand: $demandObject,
            view: $this->view,
            pluginControllerActionContext: $context,
        ));

        $assignedValues = [
            'projects' => $listEvent->getProjects(),
            'demand' => $demandObject,
            'categories' => $listEvent->getCategories(),
            'data' => $contentElementData,
        ];

        $this->view->assignMultiple($assignedValues);

        return $this->htmlResponse();
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }

    /**
     * Protected rather than private: these controllers stay open until they are made
     * `final` in 3.0.0, and a subclass that overrides an action needs the context to
     * dispatch the events itself.
     */
    protected function pluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return new PluginControllerActionContext($this->request, $this->settings);
    }
}
