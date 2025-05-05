<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\FunctionType;
use FGTCLB\AcademicPersons\Domain\Model\Location;
use FGTCLB\AcademicPersons\Domain\Model\OrganisationalUnit;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Extbase\Domain\Model\Category;

class ContractFormData
{
    protected ?OrganisationalUnit $organisationalUnit = null;
    protected ?FunctionType $functionType = null;
    protected ?\DateTime $validFrom = null;
    protected ?\DateTime $validTo = null;
    protected ?Category $employeeType = null;
    protected string $position = '';
    protected ?Location $location = null;
    protected string $room = '';
    protected string $officeHours = '';
    protected bool $publish = false;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void
    {
    }

    public function setOrganisationalUnit(?OrganisationalUnit $organisationalUnit): void
    {
        $this->organisationalUnit = $organisationalUnit;
    }

    public function getOrganisationalUnit(): ?OrganisationalUnit
    {
        return $this->organisationalUnit;
    }

    public function setFunctionType(?FunctionType $functionType): void
    {
        $this->functionType = $functionType;
    }

    public function getFunctionType(): ?FunctionType
    {
        return $this->functionType;
    }

    public function setValidFrom(?\DateTime $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    public function setValidTo(?\DateTime $validTo): void
    {
        $this->validTo = $validTo;
    }

    public function getValidTo(): ?\DateTime
    {
        return $this->validTo;
    }

    public function setEmployeeType(?Category $employeeType): void
    {
        $this->employeeType = $employeeType;
    }

    public function getEmployeeType(): ?Category
    {
        return $this->employeeType;
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setLocation(?Location $location): void
    {
        $this->location = $location;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setRoom(string $room): void
    {
        $this->room = $room;
    }

    public function getRoom(): string
    {
        return $this->room;
    }

    public function setOfficeHours(string $officeHours): void
    {
        $this->officeHours = $officeHours;
    }

    public function getOfficeHours(): string
    {
        return $this->officeHours;
    }

    public function setPublish(bool $publish): void
    {
        $this->publish = $publish;
    }

    public function isPublish(): bool
    {
        return $this->publish;
    }
}
