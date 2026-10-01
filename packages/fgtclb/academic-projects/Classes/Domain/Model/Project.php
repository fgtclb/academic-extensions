<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Model;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\GetCategoryCollectionInterface;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @api
 */
class Project extends AbstractEntity implements GetCategoryCollectionInterface
{
    protected int $doktype = 0;
    protected string $title = '';
    /** @var ObjectStorage<FileReference> */
    protected ObjectStorage $media;
    protected string $projectTitle = '';
    protected string $shortDescription = '';
    protected ?\DateTime $startDate = null;
    protected ?\DateTime $endDate = null;
    protected float $budget = 0;
    protected string $funders = '';
    protected ?CategoryCollection $attributes = null;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void
    {
        $this->media = new ObjectStorage();
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getDoktype(): int
    {
        return $this->doktype;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getMedia(): ObjectStorage
    {
        return $this->media;
    }

    public function getProjectTitle(): string
    {
        return $this->projectTitle;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * `active` or `completed`, see `ActiveState::fromEndDate()`. "Now" is taken the way the
     * list filter takes it, so the two agree at the boundary for an end date as the backend
     * stores it.
     *
     * The value of the case rather than the enum itself: Fluid cannot cast an enum to a
     * string, so `{project.activeState}` in a text, an attribute or a label key would fail
     * the whole content element (`1273753083`, Fluid 4.6 and 5.3 alike).
     *
     * @return value-of<ActiveState>
     */
    public function getActiveState(): string
    {
        return ActiveState::fromEndDate($this->endDate, new \DateTime())->value;
    }

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function getFunders(): string
    {
        return $this->funders;
    }

    public function getAttributes(): CategoryCollection
    {
        return $this->attributes ??= GeneralUtility::makeInstance(CategoryRepository::class)
            ->findByGroupAndPageId('projects', (int)$this->uid);
    }

    public function getCategoryCollection(): CategoryCollection
    {
        return $this->getAttributes();
    }
}
