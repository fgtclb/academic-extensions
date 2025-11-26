<?php declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Domain\Model\Dto;

interface ContractDemandInterface extends DemandInterface
{
    public function getContractList(): array;

    public function setContractList(array $contractList): void;

    public function getSelectedFields(): array;

    public function setSelectedFields(array $selectedFields): void;

    public function setShowPublicOnly(bool $showPublicOnly): void;

    public function getShowPublicOnly(): bool;
}
