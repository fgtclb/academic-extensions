<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Controller;

use FGTCLB\AcademicBase\Controller\DispatchModifyPluginViewEventMethodTrait;
use FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicBiteJobs\Enumeration\ListView;
use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class BiteJobsController extends ActionController
{
    use DispatchModifyPluginViewEventMethodTrait;
    use GetCurrentContentRecordMethodTrait;

    public function __construct(
        private readonly BiteJobsService $biteJobsService,
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
        $context = new PluginControllerActionContext($this->request, $this->settings);
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'jobs' => $this->biteJobsService->fetchBiteJobs($this->request, $context),
        ]);
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
