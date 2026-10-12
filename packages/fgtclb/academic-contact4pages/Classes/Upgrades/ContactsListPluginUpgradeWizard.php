<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Upgrades;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Moves the page contacts plugins of version 1.x onto the content element type
 * the plugin has been registered with since 2.0, and carries the permission of
 * backend user groups for that type over with them.
 *
 * Version 1.x registered the plugin as `ContactsList` of the extension name
 * `AcademicContacts4pages`, so its content elements carry the type
 * `academiccontacts4pages_contactslist`. 2.0 renamed the plugin to `List`.
 *
 * Until 2.4.0 the wizard doing this was `PluginUpgradeWizard`, registered as
 * `academicContact4pages_pluginUpgradeWizard`. It looked for a misspelled type
 * no release ever stored, reported nothing to do, and `upgrade:run` therefore
 * marked it as done in every installation that ran it. The core records that
 * flag by the class name of a wizard, not by its identifier, so this one is a
 * class of its own under an identifier of its own: it is offered again where
 * the old one is marked done.
 *
 * **Every row is renamed**, translations, hidden and deleted rows and workspace
 * versions included. The type is the same on a record, its translations and
 * its workspace versions, and a row left behind brings the unknown type back
 * the moment it is restored or published.
 *
 * **Backend user groups keep their access.** `tt_content.CType` is an
 * `explicitAllow` field, so a group may only edit a content element whose type
 * it lists in `explicit_allowdeny`, as `tt_content:CType:<type>`. The entry of
 * the old type is replaced by the one of the new type in place, the other
 * entries keep their order, and an entry the group already has is not added a
 * second time. An entry in the format of TYPO3 v11, with an `:ALLOW` or `:DENY`
 * suffix, is renamed with its suffix, so the core wizard that migrates that
 * format still finds it, whichever of the two runs first.
 */
#[UpgradeWizard('academicContacts4pages_pluginUpgradeWizard')]
final class ContactsListPluginUpgradeWizard implements UpgradeWizardInterface
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private const MIGRATE_CONTENT_TYPES_LIST = [
        'academiccontacts4pages_contactslist' => 'academiccontacts4pages_list',
    ];

    private const CONTENT_TYPE_PERMISSION_PREFIX = 'tt_content:CType:';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate the academic_contacts4pages plugins of version 1.x';
    }

    public function getDescription(): string
    {
        return 'Renames the content element type "academiccontacts4pages_contactslist", which version 1.x stored'
            . ' for the page contacts plugin, to "academiccontacts4pages_list", the type the plugin is registered'
            . ' with since 2.0. Translations, hidden and deleted records and workspace versions are renamed as well.'
            . ' Backend user groups allowed to edit the old type are allowed to edit the new one instead.';
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

        $connection = $this->connectionPool->getConnectionForTable('be_groups');
        foreach ($this->getBackendUserGroupsToUpdate() as $uid => $explicitAllowDeny) {
            $connection->update('be_groups', ['explicit_allowdeny' => $explicitAllowDeny], ['uid' => $uid]);
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        return $this->hasContentElementsToUpdate() || $this->getBackendUserGroupsToUpdate() !== [];
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

    private function hasContentElementsToUpdate(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in(
                    'CType',
                    $queryBuilder->quoteArrayBasedValueListToStringList(array_keys(self::MIGRATE_CONTENT_TYPES_LIST))
                ),
            )
            ->executeQuery()
            ->fetchOne() > 0;
    }

    /**
     * The groups whose permissions name an old type, with the value they get.
     *
     * The `LIKE` only narrows the rows down: it also matches a longer type that
     * starts with the old one. Whether a group has to change is decided entry by
     * entry, so such a group is neither changed nor makes the wizard necessary.
     *
     * @return array<int, string> The new `explicit_allowdeny` value by group uid
     */
    private function getBackendUserGroupsToUpdate(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll();
        $constraints = [];
        foreach (array_keys(self::MIGRATE_CONTENT_TYPES_LIST) as $oldName) {
            $constraints[] = $queryBuilder->expr()->like(
                'explicit_allowdeny',
                $queryBuilder->createNamedParameter(
                    '%' . $queryBuilder->escapeLikeWildcards(self::CONTENT_TYPE_PERMISSION_PREFIX . $oldName) . '%'
                )
            );
        }
        $rows = $queryBuilder
            ->select('uid', 'explicit_allowdeny')
            ->from('be_groups')
            ->where($queryBuilder->expr()->or(...$constraints))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $groupsToUpdate = [];
        foreach ($rows as $row) {
            $explicitAllowDeny = (string)$row['explicit_allowdeny'];
            $migrated = $this->migrateExplicitAllowDeny($explicitAllowDeny);
            if ($migrated !== null) {
                $groupsToUpdate[(int)$row['uid']] = $migrated;
            }
        }
        return $groupsToUpdate;
    }

    /**
     * @return string|null The migrated value, or null when no entry names an old type
     */
    private function migrateExplicitAllowDeny(string $explicitAllowDeny): ?string
    {
        $entries = GeneralUtility::trimExplode(',', $explicitAllowDeny, true);
        $changed = false;
        foreach ($entries as $index => $entry) {
            foreach (self::MIGRATE_CONTENT_TYPES_LIST as $oldName => $newName) {
                $oldEntry = self::CONTENT_TYPE_PERMISSION_PREFIX . $oldName;
                if ($entry === $oldEntry || str_starts_with($entry, $oldEntry . ':')) {
                    $entries[$index] = self::CONTENT_TYPE_PERMISSION_PREFIX . $newName . substr($entry, strlen($oldEntry));
                    $changed = true;
                }
            }
        }
        return $changed ? implode(',', array_unique($entries)) : null;
    }
}
