<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Model\Dto;

use FGTCLB\AcademicPrograms\Enumeration\SortingOptions;
use FGTCLB\CategoryTypes\Collection\FilterCollection;

class ProgramDemand
{
    /** @var int[] */
    protected array $pages = [];
    protected ?FilterCollection $filterCollection = null;
    protected string $sorting = '';
    protected string $sortingField = '';
    protected string $sortingDirection = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void
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

    public function getFilterCollection(): ?FilterCollection
    {
        return $this->filterCollection;
    }

    public function setFilterCollection(?FilterCollection $filterCollection): void
    {
        $this->filterCollection = $filterCollection;
    }

    public function setSorting(string $sorting): void
    {
        if (in_array($sorting, SortingOptions::getConstants())) {
            $this->sorting = $sorting;
            [$this->sortingField, $this->sortingDirection] = explode(' ', $sorting);
        }
    }

    public function getSorting(): string
    {
        return $this->sorting;
    }

    public function setSortingField(string $sortingField): void
    {
        $newSorting = $sortingField . ' ' . $this->sortingDirection;
        $this->setSorting($newSorting);
    }

    public function getSortingField(): string
    {
        return $this->sortingField;
    }

    public function setSortingDirection(string $sortingDirection): void
    {
        $newSorting = $this->sortingField . ' ' . $sortingDirection;
        $this->setSorting($newSorting);
    }

    public function getSortingDirection(): string
    {
        return $this->sortingDirection;
    }
}
