<?php declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Domain\Model\Dto;

class ContractDemand implements ContractDemandInterface
{
    /** @var array<string, mixed> */
    private array $contractList = [];

    /** @var array<string, mixed> */
    private array $selectedFields = [];

    private bool $showPublicOnly = false;

    public function getContractList(): array
    {
        return $this->contractList;
    }

    public function setContractList(array $contractList): void
    {
        $this->contractList = $contractList;
    }

    public function getSelectedFields(): array
    {
        return $this->selectedFields;
    }

    public function setSelectedFields(array $selectedFields): void
    {
        $this->selectedFields = $selectedFields;
    }

    public function setShowPublicOnly(bool $showPublicOnly): void
    {
        $this->showPublicOnly = $showPublicOnly;
    }

    public function getShowPublicOnly(): bool
    {
        return $this->showPublicOnly;
    }
}
