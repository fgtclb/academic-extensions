<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Upgrades;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('academicContact4pages_pluginUpgradeWizard')]
final class PluginUpgradeWizard implements UpgradeWizardInterface
{
    private const MIGRATE_CONTENT_TYPES_LIST = [
        'academiccontact4pages_contactslist' => 'academiccontacts4pages_list',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate academic_contacts4pages plugins from "academiccontact4pages_contactslist" to "academiccontacts4pages_list".';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function executeUpdate(): bool
    {
        foreach (self::MIGRATE_CONTENT_TYPES_LIST as $oldName => $newName) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->update('tt_content')
                ->set('CType', $newName)
                ->where(
                    $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter($oldName))
                )->executeStatement();
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        // This wizard renames a CType (not a list_type sub-type), so it must
        // detect records by CType exactly like executeUpdate() migrates them.
        // The previous `list_type` query never matched, so the wizard never ran.
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        return (bool)$queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in(
                    'CType',
                    $queryBuilder->quoteArrayBasedValueListToStringList(array_keys(self::MIGRATE_CONTENT_TYPES_LIST))
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
