<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\Address;

class AddressFormData extends AbstractFormData
{
    protected string $street;
    protected string $streetNumber;
    protected string $additional;
    protected string $zip;
    protected string $city;
    protected string $state;
    protected string $country;
    protected string $type;

    public function __construct(
        string $street,
        string $streetNumber,
        string $additional,
        string $zip,
        string $city,
        string $state,
        string $country,
        string $type
    ) {
        $this->street = $street;
        $this->streetNumber = $streetNumber;
        $this->additional = $additional;
        $this->zip = $zip;
        $this->city = $city;
        $this->state = $state;
        $this->country = $country;
        $this->type = $type;
    }

    public static function createEmpty(): self
    {
        return new self(
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        );
    }

    public static function createFromAddress(Address $address): self
    {
        return new self(
            $address->getStreet(),
            $address->getStreetNumber(),
            $address->getAdditional(),
            $address->getZip(),
            $address->getCity(),
            $address->getState(),
            $address->getCountry(),
            $address->getType()
        );
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getStreetNumber(): string
    {
        return $this->streetNumber;
    }

    public function getAdditional(): string
    {
        return $this->additional;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
