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

    private const MIGRATE_PLUGIN_SETTINGS = [
        'settings.hideCompletedProjects' => 'settings.activeState',
        'settings.filter.options' => 'settings.hideFilter',
        'settings.sorting.options' => 'settings.hideSorting',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate academic_projects FlexForm setting from "hideCompletedProjects" to "hideActiveState" selection.';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function executeUpdate(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $resultSet = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in(
                    'CType',
                    $queryBuilder->quoteArrayBasedValueListToStringList(self::MIGRATE_CONTENT_TYPES_LIST)
                ),
            )
            ->executeQuery();
        while ($record = $resultSet->fetchAssociative()) {
            /** @var array<string, mixed> $record */
            // Nothing to upgrade
            if ($record['pi_flexform'] === null || $record['pi_flexform'] === '') {
                continue;
            }
            $flexFormData = GeneralUtility::xml2array($record['pi_flexform']);
            if (!is_array($flexFormData)) {
                continue;
            }

            foreach (self::MIGRATE_PLUGIN_SETTINGS as $oldName => $newName) {
                if (isset($flexFormData['data']['sDEF']['lDEF'][$oldName]['vDEF'])) {
                    $oldValue = $flexFormData['data']['sDEF']['lDEF'][$oldName]['vDEF'];
                    $newValue = match ($newName) {
                        'settings.activeState' => ($oldValue === '1') ? ActiveState::ACTIVE->value : ActiveState::ALL->value,
                        default => $oldValue,
                    };
                    $flexFormData['data']['sDEF']['lDEF'][$newName]['vDEF'] = $newValue;
                    unset($flexFormData['data']['sDEF']['lDEF'][$oldName]);
                }
            }

            $updateQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
            $updateQueryBuilder->update('tt_content')
                ->set('pi_flexform', $this->array2xml($flexFormData))
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($record['uid'], Connection::PARAM_INT)
                    )
                )
                ->executeStatement();
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in(
                    'CType',
                    $queryBuilder->quoteArrayBasedValueListToStringList(self::MIGRATE_CONTENT_TYPES_LIST)
                ),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative() !== [];
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
     * @param string[] $input
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
