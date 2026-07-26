<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Controller;

use FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait;
use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class BiteJobsController extends ActionController
{
    use GetCurrentContentRecordMethodTrait;

    public function __construct(
        protected readonly BiteJobsService $biteJobsService
    ) {}

    public function listAction(): ResponseInterface
    {
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'jobs' => $this->biteJobsService->fetchBiteJobs($this->request),
        ]);

        return $this->htmlResponse();
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
