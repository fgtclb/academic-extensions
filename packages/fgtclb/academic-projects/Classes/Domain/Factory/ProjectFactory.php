<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Factory;

use FGTCLB\AcademicProjects\Domain\Model\Project as ProjectModel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $dataMapper = $objectManager->get(DataMapper::class);
        $projectModels = $dataMapper->map(ProjectModel::class, [$properties]);
        return $projectModels[0];
    }
}
