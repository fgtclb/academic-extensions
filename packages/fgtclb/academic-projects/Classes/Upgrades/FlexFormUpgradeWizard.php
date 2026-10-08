<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Upgrades;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('academicProjects_flexFormUpgradeWizard')]
final class FlexFormUpgradeWizard implements UpgradeWizardInterface
{
    private const MIGRATE_CONTENT_TYPES_LIST = [
        'academicprojects_projectlist',
        'academicprojects_projectlistsingle',
    ];

    /**
     * `settings.hide_completed_projects` is the name 1.x stored, the camel case name is
     * kept because the 2.0 changelog named it and a hand-edited FlexForm may carry it.
     * `settings.sorting.options` existed in 2.0 development versions only.
     */
    private const MIGRATE_PLUGIN_SETTINGS = [
        'settings.hide_completed_projects' => 'settings.activeState',
        'settings.hideCompletedProjects' => 'settings.activeState',
        'settings.filter.options' => 'settings.hideFilter',
        'settings.sorting.options' => 'settings.hideSorting',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate the plugin settings of academic_projects project lists stored before 2.0.';
    }

    public function getDescription(): string
    {
        return 'Rewrites the checkbox "settings.hide_completed_projects" of 1.x (also spelled'
            . ' "settings.hideCompletedProjects") to the selection "settings.activeState" ("active" for a checked'
            . ' box, "all" otherwise), and renames "settings.filter.options" to "settings.hideFilter" and'
            . ' "settings.sorting.options" to "settings.hideSorting", keeping their values. A new setting that is'
            . ' stored already, from a save in the backend, is kept and the old one is removed.';
    }

    public function executeUpdate(): bool
    {
        foreach ($this->contentElementsToMigrate() as $uid => $flexFormData) {
            $updateQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
            $updateQueryBuilder->update('tt_content')
                ->set('pi_flexform', $this->array2xml($flexFormData))
                ->where(
                    // The constraint must be built on the builder that executes it: a named
                    // parameter is bound to the query builder that created it.
                    $updateQueryBuilder->expr()->eq(
                        'uid',
                        $updateQueryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    )
                )
                ->executeStatement();
        }

        return true;
    }

    /**
     * Necessary only while a project list still stores one of the old settings. A project
     * list as such is no reason, every list created since 2.0 has none of them.
     */
    public function updateNecessary(): bool
    {
        foreach ($this->contentElementsToMigrate() as $_) {
            return true;
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
            PluginUpgradeWizard::class,
        ];
    }

    /**
     * Yields the migrated FlexForm data of every project list that stores one of the
     * settings before 2.0, keyed by the uid of the content element.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function contentElementsToMigrate(): \Generator
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        // Hidden, deleted, time restricted and workspace records are migrated too.
        $queryBuilder->getRestrictions()->removeAll();
        $resultSet = $queryBuilder
            ->select('uid', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in(
                    'CType',
                    $queryBuilder->quoteArrayBasedValueListToStringList(self::MIGRATE_CONTENT_TYPES_LIST)
                ),
                $queryBuilder->expr()->isNotNull('pi_flexform'),
                $queryBuilder->expr()->neq('pi_flexform', $queryBuilder->createNamedParameter('')),
            )
            ->orderBy('uid')
            ->executeQuery();

        while ($record = $resultSet->fetchAssociative()) {
            $flexFormData = GeneralUtility::xml2array((string)$record['pi_flexform']);
            if (!is_array($flexFormData)) {
                continue;
            }
            $changed = false;
            foreach (self::MIGRATE_PLUGIN_SETTINGS as $oldName => $newName) {
                if (!isset($flexFormData['data']['sDEF']['lDEF'][$oldName]['vDEF'])) {
                    continue;
                }
                // A backend save keeps stored keys the form no longer shows, so a list saved
                // since 2.0 carries the old setting next to the one an editor chose. The stored
                // new setting wins, the old one is only removed. This also settles the two old
                // names of the active state: the first one found creates the target.
                if (!isset($flexFormData['data']['sDEF']['lDEF'][$newName])) {
                    $oldValue = $flexFormData['data']['sDEF']['lDEF'][$oldName]['vDEF'];
                    $newValue = match ($newName) {
                        'settings.activeState' => ($oldValue === '1') ? ActiveState::ACTIVE->value : ActiveState::ALL->value,
                        default => $oldValue,
                    };
                    $flexFormData['data']['sDEF']['lDEF'][$newName]['vDEF'] = $newValue;
                }
                unset($flexFormData['data']['sDEF']['lDEF'][$oldName]);
                $changed = true;
            }
            if (!$changed) {
                continue;
            }

            yield (int)$record['uid'] => $flexFormData;
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    protected function array2xml(array $input = []): string
    {
        $options = [
            'parentTagMap' => [
                'data' => 'sheet',
                'sheet' => 'language',
                'language' => 'field',
                'el' => 'field',
                'field' => 'value',
                'field:el' => 'el',
                'el:_IS_NUM' => 'section',
                'section' => 'itemType',
            ],
            'disableTypeAttrib' => 2,
        ];
        $spaceInd = 4;
        $output = GeneralUtility::array2xml($input, '', 0, 'T3FlexForms', $spaceInd, $options);
        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . $output;
    }
}
