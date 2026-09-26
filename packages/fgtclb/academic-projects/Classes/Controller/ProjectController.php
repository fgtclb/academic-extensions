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
use FGTCLB\CategoryTypes\Filter\FilterTypeResolver;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ProjectController extends ActionController
{
    private ExtensionService $filterRedirectExtensionService;

    private FilterTypeResolver $filterTypeResolver;

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
        $this->redirectFilterSubmission($contentElementData);
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

        $categories = $listEvent->getCategories();
        $assignedValues = [
            'projects' => $listEvent->getProjects(),
            'demand' => $demandObject,
            'categories' => $categories,
            'filterTypes' => $this->filterTypeResolver->resolveFromSettings($categories, $this->settings),
            'data' => $contentElementData,
        ];

        $this->view->assignMultiple($assignedValues);

        return $this->htmlResponse();
    }

    /**
     * Method injection keeps the constructor, which project subclasses call, unchanged.
     * Final, and named after what it is for, so a subclass cannot collide with it.
     */
    final public function injectFilterRedirectExtensionService(ExtensionService $extensionService): void
    {
        $this->filterRedirectExtensionService = $extensionService;
    }

    /**
     * Method injection for the same reason as {@see injectFilterRedirectExtensionService()}.
     */
    final public function injectFilterTypeResolver(FilterTypeResolver $filterTypeResolver): void
    {
        $this->filterTypeResolver = $filterTypeResolver;
    }

    /**
     * Answers a submission of the filter and sorting form with a `303` to the same action,
     * carrying the selection as GET arguments, so a filtered list has a URL that can be
     * bookmarked, shared and reloaded.
     *
     * The demand is read from the body of the POST only. Extbase merges the arguments of
     * the URL into those of the body, and the URL of a filtered list carries a demand of
     * its own: merged, a category the visitor cleared would come back from the URL. A POST
     * whose body carries no demand of this plugin is another plugin's form and is left
     * alone.
     *
     * It runs before the demand event: the URL carries the visitor's selection, and a
     * listener acts on the request that follows the redirect, not on the submission.
     *
     * The redirect is thrown rather than returned. A returned one reaches the browser on
     * TYPO3 v13 only through `header()`: the response the middlewares see is a `200`, and
     * the whole page renders for nothing. The exception ends the request at the
     * `ResponsePropagation` middleware on v13 and v14 alike.
     *
     * Protected, so a subclass that overrides an action can keep the redirect.
     *
     * @param array<string, mixed> $contentElementData
     * @throws PropagateResponseException
     */
    protected function redirectFilterSubmission(array $contentElementData): void
    {
        if ($this->request->getMethod() !== 'POST') {
            return;
        }
        $pluginNamespace = $this->filterRedirectExtensionService->getPluginNamespace(
            $this->request->getControllerExtensionName(),
            $this->request->getPluginName(),
        );
        $parsedBody = $this->request->getParsedBody();
        $pluginArguments = is_array($parsedBody) ? ($parsedBody[$pluginNamespace] ?? null) : null;
        $demand = is_array($pluginArguments) ? ($pluginArguments['demand'] ?? null) : null;
        if (!is_array($demand)) {
            return;
        }

        /** @var array<string, mixed> $demand */
        $demandObject = $this->demandFactory->createDemandObject($demand, $this->settings, $contentElementData);
        throw new PropagateResponseException(
            $this->redirect(
                $this->request->getControllerActionName(),
                null,
                null,
                ['demand' => $this->demandFactory->createDemandArguments($demandObject)],
            ),
            1790226083,
        );
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
