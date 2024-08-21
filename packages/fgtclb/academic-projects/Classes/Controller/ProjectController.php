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
        protected readonly ProjectRepository $projectRepository,
        protected readonly CategoryRepository $categoryRepository
    ) {
    }

    public function listAction(?ProjectFilter $filter = null): ResponseInterface
    {
        $hideCompletedProjects = true;

        if (!$filter) {
            $filter = new ProjectFilter();

            if ($this->settings['sorting']) {
                $filter->setSorting($this->settings['sorting']);
            }
        }

        if (isset($this->settings['hide_completed_projects'])) {
            $hideCompletedProjects = (bool)$this->settings['hide_completed_projects'];
        }

        $projects = $this->projectRepository->findByFilter(
            $hideCompletedProjects,
            $filter,
            $this->configurationManager->getContentObject()->data['pages'] !== ''
                ? GeneralUtility::intExplode(',', $this->configurationManager->getContentObject()->data['pages'])
                : [],
            $this->configurationManager->getContentObject()->data['list_type'] === 'academicprojects_projectlistsingle' ? true : false
        );

        $categories = $this->categoryRepository->findAllApplicable($projects);

        $assignedValues = [
            'projects' => $projects,
            'filter' => $filter,
            'categories' => $categories,
        ];

        $this->view->assignMultiple($assignedValues);

        return $this->htmlResponse();
    }
}
