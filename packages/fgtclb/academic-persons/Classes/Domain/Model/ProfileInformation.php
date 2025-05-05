<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class ProfileInformation extends AbstractEntity
{
    protected ?Profile $profile = null;
    protected string $type = '';
    protected string $title = '';
    protected string $bodytext = '';
    protected string $link = '';
    protected ?int $year = null;
    protected ?int $yearStart = null;
    protected ?int $yearEnd = null;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function setProfile(?Profile $profile): void
    {
        $this->profile = $profile;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setBodytext(string $bodytext): void
    {
        $this->bodytext = $bodytext;
    }

    public function getBodytext(): string
    {
        return $this->bodytext;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setYear(?int $year): void
    {
        $this->year = $year;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYearStart(?int $yearStart): void
    {
        $this->yearStart = $yearStart;
    }

    public function getYearStart(): ?int
    {
        return $this->yearStart;
    }

    public function setYearEnd(?int $yearEnd): void
    {
        $this->yearEnd = $yearEnd;
    }

    public function getYearEnd(): ?int
    {
        return $this->yearEnd;
    }
}
