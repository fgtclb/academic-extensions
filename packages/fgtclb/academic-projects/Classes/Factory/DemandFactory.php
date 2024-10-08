<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Factory;

use FGTCLB\AcademicProjects\Collection\CategoryCollection;
use FGTCLB\AcademicProjects\Collection\FilterCollection;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicProjects\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DemandFactory
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {
    }

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
        $filterCollection = GeneralUtility::makeInstance(FilterCollection::class);

        // Init demand properties with plugin settings, which can be overwritten by the form
        if ($demandFromForm === null) {
            if (isset($settings['sorting'])) {
                $demand->setSorting($settings['sorting']);
            }

            if (isset($settings['categories'])
                && (int)$settings['categories'] > 0
            ) {
                $categoryCollection = $this->categoryRepository->getByDatabaseFields($contentElementData['uid']);
                $filterCollection = FilterCollection::createByCategoryCollection($categoryCollection);
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
                $categoryCollection = new CategoryCollection();

                foreach ($demandFromForm['filterCollection'] as $type => $categoriesIds) {
                    $formatType = GeneralUtility::camelCaseToLowerCaseUnderscored($type);
                    $categoriesIdList = GeneralUtility::intExplode(',', $categoriesIds);
                    $categoryFilterObject = $this->categoryRepository->findByUidListAndType(
                        $categoriesIdList,
                        CategoryTypes::cast($formatType)
                    );

                    foreach ($categoryFilterObject as $category) {
                        $categoryCollection->attach($category);
                    }
                }

                $filterCollection = FilterCollection::createByCategoryCollection($categoryCollection);
            }
        }

        $demand->setFilterCollection($filterCollection);

        // Set demand properties, which are always defined by plugin settings

        $demand->setPages(
            $contentElementData['pages'] !== ''
                ? GeneralUtility::intExplode(',', $contentElementData['pages'])
                : []
        );

        $demand->setShowSelected(
            $contentElementData['list_type'] === 'academicprojects_projectlistsingle' ? true : false
        );

        if (isset($settings['hide_completed_projects'])) {
            $demand->setHideCompletedProjects((bool)$settings['hide_completed_projects']);
        }

        return $demand;
    }
}
