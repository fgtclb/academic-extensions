<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Controller;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectFilter;
use FGTCLB\AcademicProjects\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicProjects\Domain\Repository\ProjectRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ProjectController extends ActionController
{
    public function __construct(
        protected ProjectRepository $projectRepository,
        protected CategoryRepository $categoryRepository
    ) {
    }

    public function listAction(?ProjectFilter $filter = null): ResponseInterface
    {
        if (!$filter) {
            $filter = new ProjectFilter();

            if ($this->settings['sorting']) {
                $filter->setSorting($this->settings['sorting']);
            }
        }

        $projects = $this->projectRepository->findByFilter(
            $filter,
            GeneralUtility::intExplode(
                ',',
                $this->configurationManager->getContentObject()
                    ? $this->configurationManager->getContentObject()->data['pages']
                    : []
            )
        );

        $categories =$this->categoryRepository->findAllApplicable($projects);

        $assignedValues = [
            'projects' => $projects,
            'filter' => $filter,
            'categories' => $categories,
        ];

        $this->view->assignMultiple($assignedValues);

        return $this->htmlResponse();
    }
}
