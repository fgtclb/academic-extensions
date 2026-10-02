<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Controller;

use FGTCLB\AcademicBase\Controller\DispatchModifyPluginViewEventMethodTrait;
use FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait;
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

final class ProjectController extends ActionController
{
    use DispatchModifyPluginViewEventMethodTrait;
    use GetCurrentContentRecordMethodTrait;

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly DemandFactory $demandFactory,
        private readonly ExtensionService $filterRedirectExtensionService,
        private readonly FilterTypeResolver $filterTypeResolver,
    ) {}

    /**
     * `visitorSelection` tells the template whether the request carried a demand. Only a
     * request without one shows the selection the content element presets, so a link back
     * to that selection is useful only while it is `true`.
     *
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function listAction(?array $demand = null): ResponseInterface
    {
        $context = $this->pluginControllerActionContext();
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $this->redirectFilterSubmission($contentElementData);
        $demandObject = $this->demandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

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
            'visitorSelection' => $demand !== null,
            'data' => $contentElementData,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
        ];

        $this->view->assignMultiple($assignedValues);
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
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
     * @param array<string, mixed> $contentElementData
     * @throws PropagateResponseException
     */
    private function redirectFilterSubmission(array $contentElementData): void
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

    private function pluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return new PluginControllerActionContext($this->request, $this->settings);
    }
}
