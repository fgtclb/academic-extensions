<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Collection\FileReferenceCollection;
use FGTCLB\EducationalCourse\Domain\Repository\CourseCategoryRepository;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Course
{
    protected readonly int $uid;

    protected string $title;

    protected string $subtitle;

    protected string $abstract;

    protected string $jobProfile;

    protected string $performanceScope;

    protected string $prerequisites;

    protected CategoryCollection $attributes;

    protected FileReferenceCollection $media;

    /**
     * @throws CategoryExistException
     * @throws Exception
     * @throws DBALException
     */
    public function __construct(int $databaseId)
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $page = $pageRepository->getPage($databaseId);
        $this->uid = $page['uid'];
        $this->title = $page['title'] ?? '';
        $this->subtitle = $page['subtitle'] ?? '';
        $this->abstract = $page['abstract'] ?? '';
        $this->jobProfile = $page['job_profile'] ?? '';
        $this->performanceScope = $page['performance_scope'] ?? '';
        $this->prerequisites = $page['prerequisites'] ?? '';

        $this->attributes = GeneralUtility::makeInstance(CourseCategoryRepository::class)
            ->findAllByPageId($databaseId);

        $this->media = self::loadMedia($this->uid);
    }

    protected static function loadMedia(int $pageId): FileReferenceCollection
    {
        return FileReferenceCollection::getCollectionByPageIdAndField($pageId, 'media');
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function getAbstract(): string
    {
        return $this->abstract;
    }

    public function getAttributes(): CategoryCollection
    {
        return $this->attributes;
    }

    public function getMedia(): FileReferenceCollection
    {
        return $this->media;
    }

    public function getJobProfile(): string
    {
        return $this->jobProfile;
    }

    public function getPerformanceScope(): string
    {
        return $this->performanceScope;
    }

    public function getPrerequisites(): string
    {
        return $this->prerequisites;
    }

    public function getUid(): int
    {
        return $this->uid;
    }
}
