<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Controller;

use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use FGTCLB\AcademicPartners\Factory\DemandFactory;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class PartnerController extends ActionController
{
    public function __construct(
        protected PartnerRepository $partnerRepository,
        protected PartnershipRepository $partnershipRepository,
        protected CategoryRepository $categoryRepository,
        protected DemandFactory $partnerDemandFactory
    ) {}

    /**
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function listAction(?array $demand = null): ResponseInterface
    {
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $demandObject = $this->partnerDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        $partners = $this->partnerRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('partners', ...array_values($partners->toArray()));

        $this->view->assignMultiple([
            'partners' => $partners,
            'data' => $contentElementData,
            'demand' => $demandObject,
            'categories' => $categories,
        ]);

        return $this->htmlResponse();
    }

    /**
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function mapAction(?array $demand = null): ResponseInterface
    {
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $demandObject = $this->partnerDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        $partners = $this->partnerRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('partners', ...array_values($partners->toArray()));

        $this->view->assignMultiple([
            'partners' => $partners,
            'data' => $contentElementData,
            'demand' => $demandObject,
            'categories' => $categories,
        ]);

        return $this->htmlResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function partnershipsListAction(): ResponseInterface
    {
        /** @var array<string, mixed> */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $partnerships = $this->partnershipRepository->findByPid((int)($contentElementData['pid'] ?? 0));

        $roles = [];
        foreach ($partnerships as $partnership) {
            $role = $partnership->getRole();
            if ($role !== null) {
                $roles[$role->getUid()] = $role;
            }
        }

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'partnerships' => $partnerships,
            'partnershipRoles' => $roles,
        ]);

        return $this->htmlResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function partnershipsTeaserAction(): ResponseInterface
    {
        /** @var array<string, mixed> */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $partnerships = $this->partnershipRepository->findByPid((int)($contentElementData['pid'] ?? 0));

        $roles = [];
        foreach ($partnerships as $partnership) {
            $role = $partnership->getRole();
            if ($role !== null) {
                $roles[$role->getUid()] = $role;
            }
        }

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'partnerships' => $partnerships,
            'partnershipRoles' => $roles,
        ]);

        return $this->htmlResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function pagesListAction(): ResponseInterface
    {
        /** @var array<string, mixed> */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $partnerships = $this->partnershipRepository->findByPartner((int)($contentElementData['pid'] ?? 0));

        $pages = [];
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        foreach ($partnerships as $partnership) {
            $pageUid = $partnership->getPage();
            $page = $pageRepository->getPage($pageUid);
            if ($page !== null) {
                if (!empty($this->settings['doktype'])) {
                    if ($page['doktype'] === (int)$this->settings['doktype']) {
                        $pages[strtolower($page['title'])] = $page;
                    }
                } else {
                    $pages[strtolower($page['title'])] = $page;
                }
            }
        }
        ksort($pages);

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'pages' => $pages,
        ]);

        return $this->htmlResponse();
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
