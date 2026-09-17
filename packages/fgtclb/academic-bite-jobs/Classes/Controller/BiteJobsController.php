<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Controller;

use FGTCLB\AcademicBiteJobs\Enumeration\ListView;
use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class BiteJobsController extends ActionController
{
    public function __construct(
        protected readonly BiteJobsService $biteJobsService
    ) {}

    /**
     * Normalises the stored view value before the view is resolved, so the view and every
     * template override read a value that names an existing partial.
     */
    protected function initializeListAction(): void
    {
        $jobsSettings = is_array($this->settings['jobs'] ?? null) ? $this->settings['jobs'] : [];
        $view = $jobsSettings['view'] ?? '';
        $jobsSettings['view'] = ListView::fromStoredValue(is_string($view) ? $view : '')->value;
        $this->settings['jobs'] = $jobsSettings;
    }

    public function listAction(): ResponseInterface
    {
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'jobs' => $this->biteJobsService->fetchBiteJobs($this->request),
        ]);

        return $this->htmlResponse();
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
