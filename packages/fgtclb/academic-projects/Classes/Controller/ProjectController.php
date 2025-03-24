<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Controller;

use FGTCLB\AcademicProjects\Domain\Repository\ProjectRepository;
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
        /** @var array<string, mixed> */
        $contentElementData = $this->getContentObject()?->data ?? [];
        $demandObject = $this->demandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        $projects = $this->projectRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('projects', ...array_values($projects->toArray()));

        $assignedValues = [
            'projects' => $projects,
            'demand' => $demandObject,
            'categories' => $categories,
            'data' => $contentElementData,
        ];

        $this->view->assignMultiple($assignedValues);

        return $this->htmlResponse();
    }

    private function getContentObject(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
