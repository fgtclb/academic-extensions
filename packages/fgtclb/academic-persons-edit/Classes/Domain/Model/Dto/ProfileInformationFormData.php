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
    protected ?\DateTime $date = null;
    protected ?\DateTime $dateStart = null;
    protected ?\DateTime $dateEnd = null;
    protected bool $yearOnly = false;

    public function __construct(
        string $type = '',
        string $title = '',
        string $bodytext = '',
        string $link = '',
        ?\DateTime $date = null,
        ?\DateTime $dateStart = null,
        ?\DateTime $dateEnd = null,
        bool $yearOnly = false
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->bodytext = $bodytext;
        $this->link = $link;
        $this->date = $date;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
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
        $instance->date = $profileInformation->getDate();
        $instance->dateStart = $profileInformation->getDateStart();
        $instance->dateEnd = $profileInformation->getDateEnd();
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

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function getDateStart(): ?\DateTime
    {
        return $this->dateStart;
    }

    public function getDateEnd(): ?\DateTime
    {
        return $this->dateEnd;
    }

    public function isYearOnly(): bool
    {
        return $this->yearOnly;
    }
}
