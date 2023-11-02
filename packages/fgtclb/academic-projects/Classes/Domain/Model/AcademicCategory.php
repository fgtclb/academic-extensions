<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\AcademicProjects\Domain\Collection\CategoryCollection;
use FGTCLB\AcademicProjects\Domain\Enumeration\CategoryTypes;
use FGTCLB\AcademicProjects\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicProjects\Exception\Domain\CategoryExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AcademicCategory
 */
class AcademicCategory
{
    protected int $uid;

    protected CategoryTypes $type;

    protected string $title;

    protected bool $active = false;

    protected ?CategoryCollection $children = null;

    /**
     * @throws CategoryExistException
     * @throws DBALException
     * @throws Exception
     */
    public function __construct(
        int $uid,
        CategoryTypes $type,
        string $title
    ) {
        $this->uid = $uid;
        $this->type = $type;
        $this->title = $title;
        $this->children = GeneralUtility::makeInstance(CategoryRepository::class)
            ->findChildren($this->uid);
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getType(): CategoryTypes
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getChildren(): ?CategoryCollection
    {
        return $this->children;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getActive(): bool
    {
        return $this->active;
    }
}
