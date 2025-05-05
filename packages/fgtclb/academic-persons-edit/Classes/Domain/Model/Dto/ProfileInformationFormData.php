<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;

class ProfileInformationFormData extends AbstractFormData
{
    protected string $type;
    protected string $title;
    protected string $bodytext;
    protected string $link;
    protected ?int $year;
    protected ?int $yearStart;
    protected ?int $yearEnd;

    public function __construct(
        string $type,
        string $title,
        string $bodytext,
        string $link,
        ?int $year,
        ?int $yearStart,
        ?int $yearEnd
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->bodytext = $bodytext;
        $this->link = $link;
        $this->year = $year;
        $this->yearStart = $yearStart;
        $this->yearEnd = $yearEnd;
    }

    public static function createEmpty(): self
    {
        return new self(
            '',
            '',
            '',
            '',
            null,
            null,
            null
        );
    }

    public static function createFromProfileInformation(ProfileInformation $profileInformation): self
    {
        return new self(
            $profileInformation->getType(),
            $profileInformation->getTitle(),
            $profileInformation->getBodytext(),
            $profileInformation->getLink(),
            $profileInformation->getYear(),
            $profileInformation->getYearStart(),
            $profileInformation->getYearEnd()
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBodytext(): string
    {
        return $this->bodytext;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getYearStart(): ?int
    {
        return $this->yearStart;
    }

    public function getYearEnd(): ?int
    {
        return $this->yearEnd;
    }
}
