<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Model\Dto;

use FGTCLB\AcademicProjects\Enumeration\SortingOptions;
use FGTCLB\CategoryTypes\Collection\FilterCollection;

class ProjectDemand
{
    /** @var int[] */
    protected array $pages = [];
    protected bool $showSelected = false;
    protected string $sorting = '';
    protected string $sortingField = '';
    protected string $sortingDirection = '';
    protected string $activeState = 'all';
    protected ?FilterCollection $filterCollection = null;

    public function __construct()
    {
        $this->setSorting(SortingOptions::__default);
    }

    /**
     * @param int[] $pages
     */
    public function setPages(array $pages): void
    {
        $this->pages = $pages;
    }

    /**
     * @return int[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    public function setShowSelected(bool $showSelected): void
    {
        $this->showSelected = $showSelected;
    }

    public function getShowSelected(): bool
    {
        return $this->showSelected;
    }

    public function setActiveState(string $activeState): void
    {
        if (ActiveState::tryFrom($activeState) === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid $activeState value given, possible vales are "%s". Provided: %s',
                    implode(', ', ActiveState::values()),
                    $activeState,
                ),
                1743627158
            );
        }
        $this->activeState = $activeState;
    }

    public function getActiveState(): string
    {
        return $this->activeState;
    }

    /**
     * @return string[]
     */
    public function getPossibleActiveStates(): array
    {
        return ActiveState::values();
    }

    public function setFilterCollection(?FilterCollection $filterCollection): void
    {
        $this->filterCollection = $filterCollection;
    }

    public function getFilterCollection(): ?FilterCollection
    {
        return $this->filterCollection;
    }

    public function setSorting(string $sorting): void
    {
        if (in_array($sorting, SortingOptions::getConstants(), true)) {
            $this->sorting = $sorting;
            [
                $this->sortingField,
                $this->sortingDirection,
            ] = array_pad(explode(' ', $sorting, 2), 2, '');
        }
    }

    public function getSorting(): string
    {
        return $this->sorting;
    }

    public function setSortingField(string $sortingField): void
    {
        $this->setSorting($sortingField . ' ' . $this->sortingDirection);
    }

    public function getSortingField(): string
    {
        return $this->sortingField;
    }

    public function setSortingDirection(string $sortingDirection): void
    {
        $this->setSorting($this->sortingField . ' ' . $sortingDirection);
    }

    public function getSortingDirection(): string
    {
        return $this->sortingDirection;
    }
}
