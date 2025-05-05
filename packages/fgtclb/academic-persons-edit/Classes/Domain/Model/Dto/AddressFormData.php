<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

class AddressFormData
{
    protected string $street = '';
    protected string $streetNumber = '';
    protected string $additional = '';
    protected string $zip = '';
    protected string $city = '';
    protected string $state = '';
    protected string $country = '';
    protected string $type = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreetNumber(string $streetNumber): void
    {
        $this->streetNumber = $streetNumber;
    }

    public function getStreetNumber(): string
    {
        return $this->streetNumber;
    }

    public function setAdditional(string $additional): void
    {
        $this->additional = $additional;
    }

    public function getAdditional(): string
    {
        return $this->additional;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
