<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;

class PhoneNumberFormData extends AbstractFormData
{
    protected string $phoneNumber;
    protected string $type;

    public function __construct(
        string $phoneNumber,
        string $type
    ) {
        $this->phoneNumber = $phoneNumber;
        $this->type = $type;
    }

    public static function createEmpty(): self
    {
        return new self(
            '',
            ''
        );
    }

    public static function createFromPhoneNumber(PhoneNumber $phoneNumber): self
    {
        return new self(
            $phoneNumber->getPhoneNumber(),
            $phoneNumber->getType()
        );
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
