<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Model;

use Doctrine\DBAL\DBALException;
use FGTCLB\AcademicProjects\Domain\Collection\CategoryCollection;
use FGTCLB\AcademicProjects\Domain\Enumeration\Page;
use FGTCLB\AcademicProjects\Domain\Repository\CategoryRepository;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use InvalidArgumentException;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Model class for the page type "project"
 */
class Project extends AbstractEntity
{
    protected string $title = '';

    protected string $projectTitle = '';

    protected string $shortDescription = '';

    protected ?\DateTime $startDate = null;

    protected ?\DateTime $endDate = null;

    protected float $budget = 0;

    protected string $funders = '';

    protected ?CategoryCollection $attributes = null;

    /**
     * @throws CategoryExistException
     * @throws \Exception
     * @throws DBALException
     * @throws InvalidArgumentException
     */
    public function __construct(int $databaseId)
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $page = $pageRepository->getPage($databaseId);
        if (count($page) === 0) {
            throw new InvalidArgumentException(
                'Page not found',
                1683811343841
            );
        }

        $this->pid = $page['pid'];
        $this->uid = $page['uid'];
        $this->title = $page['title'] ?? '';
        $this->projectTitle = $page['tx_academicprojects_project_title'] ?? '';
        $this->shortDescription = $page['tx_academicprojects_short_description'] ?? '';
        $this->startDate = $page['tx_academicprojects_start_date'] ? new \DateTime('@' . $page['tx_academicprojects_start_date']) : null;
        $this->endDate = $page['tx_academicprojects_end_date'] ? new \DateTime('@' . $page['tx_academicprojects_end_date']) : null;

        $this->attributes = GeneralUtility::makeInstance(CategoryRepository::class)
            ->findAllByPageId($databaseId);
    }

    /**
     * @throws \Exception
     * @throws DBALException
     * @throws CategoryExistException
     */
    public static function loadFromLink(int $linkId): Project
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pageToResolve = $pageRepository->getPage($linkId);
        $originalPage = match ($pageToResolve['doktype']) {
            PageRepository::DOKTYPE_SHORTCUT => $pageRepository->resolveShortcutPage($pageToResolve),
            default => throw new InvalidArgumentException(
                'Calling with doktypes other than 4 or 7 not allowed',
                1685532706120
            ),
        };
        if ($originalPage['doktype'] !== Page::TYPE_EDUCATIONAL_PROJECT) {
            throw new \RuntimeException(
                sprintf('Page "%d" has no Project page linked', $linkId),
                1685532982084
            );
        }

        return new self($originalPage['uid']);
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getTitle(): string
    {
        return $this->title;
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

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function getFunders(): string
    {
        return $this->funders;
    }

    public function getAttributes(): ?CategoryCollection
    {
        return $this->attributes;
    }
}
