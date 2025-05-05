<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\Email;

class EmailFormData extends AbstractFormData
{
    protected string $email;
    protected string $type;

    public function __construct(
        string $email,
        string $type
    ) {
        $this->email = $email;
        $this->type = $type;
    }

    public static function createEmpty(): self
    {
        return new self(
            '',
            ''
        );
    }

    public static function createFromEmail(Email $email): self
    {
        return new self(
            $email->getEmail(),
            $email->getType()
        );
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
