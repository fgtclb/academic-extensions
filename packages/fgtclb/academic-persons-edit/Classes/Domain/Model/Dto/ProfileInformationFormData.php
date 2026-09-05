<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;

/**
 * @internal to be used only in `EXT:academic_persons_edit` and not part of public API. May change at any time.
 */
class ProfileInformationFormData extends AbstractFormData
{
    protected string $type = '';
    protected string $title = '';
    protected string $bodytext = '';
    protected string $link = '';
    protected ?\DateTime $year = null;
    protected ?\DateTime $yearStart = null;
    protected ?\DateTime $yearEnd = null;
    protected bool $yearOnly = false;

    public function __construct(
        string $type = '',
        string $title = '',
        string $bodytext = '',
        string $link = '',
        ?\DateTime $year = null,
        ?\DateTime $yearStart = null,
        ?\DateTime $yearEnd = null,
        bool $yearOnly = false
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->bodytext = $bodytext;
        $this->link = $link;
        $this->year = $year;
        $this->yearStart = $yearStart;
        $this->yearEnd = $yearEnd;
        $this->yearOnly = $yearOnly;
    }

    public static function createEmptyForType(string $type): self
    {
        $instance = new static();
        $instance->type = $type;
        return $instance;
    }

    public static function createFromProfileInformation(ProfileInformation $profileInformation): self
    {
        $instance = new static();
        $instance->type = $profileInformation->getType();
        $instance->title = $profileInformation->getTitle();
        $instance->bodytext = $profileInformation->getBodytext();
        $instance->link = $profileInformation->getLink();
        $instance->year = $profileInformation->getYear();
        $instance->yearStart = $profileInformation->getYearStart();
        $instance->yearEnd = $profileInformation->getYearEnd();
        $instance->yearOnly = $profileInformation->isYearOnly();
        return $instance;
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

    public function getYear(): ?\DateTime
    {
        return $this->year;
    }

    public function getYearStart(): ?\DateTime
    {
        return $this->yearStart;
    }

    public function getYearEnd(): ?\DateTime
    {
        return $this->yearEnd;
    }

    public function isYearOnly(): bool
    {
        return $this->yearOnly;
    }
}
