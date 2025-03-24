<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Factory;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DemandFactory
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {}

    /**
     * @param ?array<string, mixed> $demandFromForm
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $contentElementData
     */
    public function createDemandObject(
        ?array $demandFromForm,
        array $settings,
        array $contentElementData
    ): ProjectDemand {
        $demand = GeneralUtility::makeInstance(ProjectDemand::class);
        $categoryCollection = null;

        // Init demand properties with plugin settings, which can be overwritten by the form
        if ($demandFromForm === null) {
            if (isset($settings['sorting'])) {
                $demand->setSorting($settings['sorting']);
            }
            if (isset($settings['categories'])
                && (int)$settings['categories'] > 0
            ) {
                $categoryCollection = $this->categoryRepository->getByDatabaseFields('projects', (int)$contentElementData['uid']);
            }
        } else {
            // Set demand properties, if form data is available
            if (isset($demandFromForm['sorting'])) {
                $demand->setSorting($demandFromForm['sorting']);
            }

            if (isset($demandFromForm['sortingField'])) {
                $demand->setSortingField($demandFromForm['sortingField']);
            }

            if (isset($demandFromForm['sortingDirection'])) {
                $demand->setSortingDirection($demandFromForm['sortingDirection']);
            }

            if (isset($demandFromForm['filterCollection'])) {
                $categoryUids = [];
                foreach ($demandFromForm['filterCollection'] as $uids) {
                    $categoryUids = array_merge($categoryUids, GeneralUtility::intExplode(',', $uids));
                }

                $categoryCollection = $this->categoryRepository->findByGroupAndUidList(
                    'projects',
                    $categoryUids,
                );
            }
        }

        if ($categoryCollection !== null) {
            $demand->setFilterCollection(new FilterCollection($categoryCollection));
        }

        // Set demand properties, which are always defined by plugin settings
        $demand->setPages(
            $contentElementData['pages'] !== ''
                ? GeneralUtility::intExplode(',', $contentElementData['pages'])
                : []
        );
        $demand->setShowSelected(
            $contentElementData['list_type'] === 'academicprojects_projectlistsingle'
        );
        if (isset($settings['hide_completed_projects'])) {
            $demand->setHideCompletedProjects((bool)$settings['hide_completed_projects']);
        }
        return $demand;
    }
}
