<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Factory;

use FGTCLB\AcademicProjects\Domain\Model\Project as ProjectModel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Factory class for projects
 */
class ProjectFactory
{
    /**
     * @param array<int|string, mixed> $properties page properties of the current page
     * @return ProjectModel
     */
    public function get(array $properties): ProjectModel
    {
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $projectModels = $dataMapper->map(ProjectModel::class, [$properties]);
        return $projectModels[0];
    }
}
