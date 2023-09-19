<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Model\Dto;

class ProjectFilter
{
    protected string $sorting = '';

    /** @var int[] */
    protected array $filterCategories = [];

    public function __construct()
    {
    }

    public function setSorting(string $sorting): void
    {
        $this->sorting = $sorting;
    }

    public function getSorting(): string
    {
        return $this->sorting;
    }

    /**
     * @param int[] $filterCategories
     */
    public function setFilterCategories(array $filterCategories): void
    {
        $this->filterCategories = $filterCategories;
    }

    /**
     * @return int[]
     */
    public function getFilterCategories(): array
    {
        return $this->filterCategories;
    }
}
