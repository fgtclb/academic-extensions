<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Model;

use FGTCLB\AcademicPrograms\Collection\CategoryCollection;
use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicPrograms\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Category implements \Stringable
{
    protected ?CategoryTypes $type;

    protected ?CategoryCollection $children = null;

    public function __construct(
        protected int $uid,
        protected int $parentId,
        protected string $title,
        string $type = '',
        protected bool $disabled = false
    ) {
        if ($type === 'default' || $type === '') {
            $type = null;
        } else {
            $type = CategoryTypes::cast($type);
        }

        $this->type = $type;

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $this->children = $categoryRepository->findChildren($this->uid);
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getType(): ?CategoryTypes
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

    public function hasParent(): bool
    {
        return $this->parentId > 0;
    }

    public function getParent(): ?Category
    {
        if (!$this->hasParent()) {
            return null;
        }

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        return $categoryRepository->findParent($this->parentId);
    }

    public function isRoot(): bool
    {
        $parent = $this->getParent();
        if ($parent === null
            || (string)$this->type !== (string)$parent->getType()
        ) {
            return true;
        }
        return false;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function __toString(): string
    {
        return (string)$this->uid;
    }
}
