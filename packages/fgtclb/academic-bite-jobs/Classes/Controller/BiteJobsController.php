<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Controller;

use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class BiteJobsController extends ActionController
{
    protected BiteJobsService $biteJobsService;
    public function __construct(BiteJobsService $biteJobsService)
    {
        $this->biteJobsService = $biteJobsService ?? GeneralUtility::makeInstance(BiteJobsService::class);
    }
    public function listAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            'jobs' => $this->biteJobsService->fetchBiteJobs(),
            'jobRelations' => $this->biteJobsService->findCustomjobRelations(),
        ]);

        return $this->htmlResponse();
    }
}
