<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Controller;

use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class BiteJobsController extends ActionController
{
    public function __construct(
        protected readonly BiteJobsService $biteJobsService
    ) {}

    public function listAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            'jobs' => $this->biteJobsService->fetchBiteJobs($this->request),
            'jobRelations' => $this->biteJobsService->findCustomjobRelations(),
        ]);

        return $this->htmlResponse();
    }
}
