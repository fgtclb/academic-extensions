<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Upgrades;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('academicProjects_pluginUpgradeWizard')]
final class PluginUpgradeWizard implements UpgradeWizardInterface
{
    private const MIGRATE_CONTENT_TYPES_LIST = [
        'academicprojects_projectlist',
        'academicprojects_projectlistsingle',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate academic_projects plugins from "list_type" to "CType".';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function executeUpdate(): bool
    {
        foreach (self::MIGRATE_CONTENT_TYPES_LIST as $contentType) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->update('tt_content')
                ->set('CType', $contentType)
                ->set('list_type', '')
                ->where(
                    $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                    $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter($contentType))
                )->executeStatement();
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        return (bool)$queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in(
                    'list_type',
                    $queryBuilder->quoteArrayBasedValueListToStringList(self::MIGRATE_CONTENT_TYPES_LIST)
                ),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
