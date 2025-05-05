<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\FunctionType;
use FGTCLB\AcademicPersons\Domain\Model\Location;
use FGTCLB\AcademicPersons\Domain\Model\OrganisationalUnit;

class ContractFormData extends AbstractFormData
{
    protected ?OrganisationalUnit $organisationalUnit;
    protected ?FunctionType $functionType;
    protected ?\DateTime $validFrom;
    protected ?\DateTime $validTo;
    protected string $position;
    protected ?Location $location;
    protected string $room;
    protected string $officeHours;
    protected bool $publish;

    public function __construct(
        ?OrganisationalUnit $organisationalUnit,
        ?FunctionType $functionType,
        ?\DateTime $validFrom,
        ?\DateTime $validTo,
        string $position,
        ?Location $location,
        string $room,
        string $officeHours,
        bool $publish
    ) {
        $this->organisationalUnit = $organisationalUnit;
        $this->functionType = $functionType;
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->position = $position;
        $this->location = $location;
        $this->room = $room;
        $this->officeHours = $officeHours;
        $this->publish = $publish;
    }

    public static function createEmpty(): self
    {
        return new self(
            null,
            null,
            null,
            null,
            '',
            null,
            '',
            '',
            false
        );
    }

    public static function createFromContract(Contract $contract): self
    {
        return new self(
            $contract->getOrganisationalUnit(),
            $contract->getFunctionType(),
            $contract->getValidFrom(),
            $contract->getValidTo(),
            $contract->getPosition(),
            $contract->getLocation(),
            $contract->getRoom(),
            $contract->getOfficeHours(),
            $contract->isPublish()
        );
    }

    public function getOrganisationalUnit(): ?OrganisationalUnit
    {
        return $this->organisationalUnit;
    }

    public function getFunctionType(): ?FunctionType
    {
        return $this->functionType;
    }

    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    public function getValidTo(): ?\DateTime
    {
        return $this->validTo;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function getRoom(): string
    {
        return $this->room;
    }

    public function getOfficeHours(): string
    {
        return $this->officeHours;
    }

    public function isPublish(): bool
    {
        return $this->publish;
    }
}
