<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Factory;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Filter\CategoryFilterNormalizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DemandFactory
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryFilterNormalizer $categoryFilterNormalizer,
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

        // The content element presets every part of the form for a request without a demand,
        // and a part it hides for every request: a hidden part submits nothing, and neither a
        // submission nor a URL may change what the editor did not offer to change. Categories
        // may only narrow a hidden filter, see resolveCategories() (ACE-916).
        $hideActiveState = (bool)($settings['hideActiveState'] ?? false);
        $hideSorting = (bool)($settings['hideSorting'] ?? false);
        $hideFilter = (bool)($settings['hideFilter'] ?? false);

        if ($demandFromForm === null || $hideActiveState) {
            if (isset($settings['activeState'])) {
                $demand->setActiveState(ActiveState::tryFromDefault($this->optionValue($settings['activeState']))->value);
            }
        } elseif (isset($demandFromForm['activeState'])) {
            $demand->setActiveState(ActiveState::tryFromDefault($this->optionValue($demandFromForm['activeState']))->value);
        }

        if ($demandFromForm === null || $hideSorting) {
            if (isset($settings['sorting'])) {
                $demand->setSorting($this->optionValue($settings['sorting']));
            }
        } else {
            if (isset($demandFromForm['sorting'])) {
                $demand->setSorting($this->optionValue($demandFromForm['sorting']));
            }
            if (isset($demandFromForm['sortingField'])) {
                $demand->setSortingField($this->optionValue($demandFromForm['sortingField']));
            }
            if (isset($demandFromForm['sortingDirection'])) {
                $demand->setSortingDirection($this->optionValue($demandFromForm['sortingDirection']));
            }
        }

        $categoryCollection = $this->resolveCategories(
            $demandFromForm,
            $settings,
            (int)($contentElementData['uid'] ?? 0),
            $hideFilter,
        );

        if ($categoryCollection !== null) {
            $demand->setFilterCollection(new FilterCollection($categoryCollection));
        }

        // Set demand properties, which are always defined by plugin settings
        $demand->setPages([]);
        if (isset($contentElementData['pages'])
            && is_string($contentElementData['pages'])
            && $contentElementData['pages'] !== ''
        ) {
            $demand->setPages(GeneralUtility::intExplode(',', $contentElementData['pages']));
        }
        $demand->setShowSelected(
            ($contentElementData['CType'] ?? '') === 'academicprojects_projectlistsingle'
        );
        $demand->setShowHiddenRecords((bool)($settings['showHiddenRecords'] ?? false));

        return $demand;
    }

    /**
     * The categories the list is filtered by, `null` for none.
     *
     * A request without a demand gets the preset categories of the element. For a demand,
     * {@see CategoryFilterNormalizer::toUidListKeepingPresets()} decides which presets stay:
     * every one where the element hides the filter, so a submission or a link narrows the
     * list but never leaves the presets, and those of the category types a form does not
     * offer otherwise. A demand without a filter has none, unless the filter is hidden.
     *
     * @param ?array<string, mixed> $demandFromForm
     * @param array<string, mixed> $settings
     */
    private function resolveCategories(
        ?array $demandFromForm,
        array $settings,
        int $contentElementUid,
        bool $hideFilter,
    ): ?CategoryCollection {
        $filter = $demandFromForm['filterCollection'] ?? null;
        $presetCategories = null;
        if (($demandFromForm === null || $this->categoryFilterNormalizer->keepsPresets($filter, $hideFilter))
            && isset($settings['categories'])
            && (int)$settings['categories'] > 0
        ) {
            $presetCategories = $this->categoryRepository->getByDatabaseFields('projects', $contentElementUid);
        }
        if ($demandFromForm === null || $filter === null) {
            return $presetCategories;
        }

        return $this->categoryRepository->findByGroupAndUidList(
            'projects',
            $this->categoryFilterNormalizer->toUidListKeepingPresets(
                $filter,
                $presetCategories?->getAllCategoriesByType() ?? [],
                $hideFilter,
            ),
        );
    }

    /**
     * A value of the settings or of a request as the string the setters of the demand
     * take. A value that is not a string becomes the empty string, which no setter
     * accepts, so it is ignored like an unknown option instead of raising a `TypeError`.
     * A request carries an array for `demand[sortingField][]=title`, which Extbase hands
     * over as it came, and a TypoScript setting is an array as soon as it has a child.
     */
    private function optionValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * The reverse of {@see createDemandObject()}: the arguments of a list URL that
     * demands the same selection, read from what the factory accepted rather than from
     * the request. A category that is not a project category, a uid no category has, an
     * active state the list does not offer and the referrer and request hash fields of
     * the form are therefore never part of it.
     *
     * The sorting and the active state are always included, even when they equal the
     * default. Only a request without any demand argument applies the content element's
     * presets, so a visitor who cleared a preset category would otherwise get it back.
     * Presets the demand keeps, those of a hidden part of the form included, are in the
     * demand already, so the URL carries them too.
     * The categories are one comma separated list, see
     * {@see CategoryFilterNormalizer::toFilterArgument()}, and are left out when nothing
     * is filtered.
     *
     * @return array<string, mixed>
     */
    public function createDemandArguments(ProjectDemand $demand): array
    {
        $arguments = [
            'sortingField' => $demand->getSortingField(),
            'sortingDirection' => $demand->getSortingDirection(),
            'activeState' => $demand->getActiveState(),
        ];
        $filterArgument = $this->categoryFilterNormalizer->toFilterArgument($demand->getFilterCollection());
        if ($filterArgument !== '') {
            $arguments['filterCollection'] = ['categories' => $filterArgument];
        }

        return $arguments;
    }
}
