<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Upgrades;

use FGTCLB\AcademicBiteJobs\Enumeration\ListView;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Repairs the plugin settings of job list content elements that version 2.1 left behind.
 *
 * Version 2.1 renamed the view values `ListView`, `CardView` and `TableView` without
 * migrating them. The list plugin reads old values through
 * {@see ListView::fromStoredValue()} anyway; this wizard applies the same rule to the
 * stored data, so the backend form shows the view the frontend renders.
 *
 * The same version removed two settings from the data structure without removing them from
 * the stored FlexForms. `settings.jobs.groupBy` still reaches the plugin as a setting, and
 * a value of `thema` would group the list by a field the postings no longer carry, so the
 * wizard drops both of them.
 *
 * Every other field is left as it is, and so is a FlexForm that does not parse.
 */
#[UpgradeWizard('academicBiteJobs_listViewFlexFormUpgradeWizard')]
final class ListViewFlexFormUpgradeWizard implements UpgradeWizardInterface
{
    private const CONTENT_TYPES = [
        'academicbitejobs_list',
    ];

    private const VIEW_FIELD = 'settings.jobs.view';

    /**
     * Settings version 2.1 removed from the data structure, see
     * `Documentation/Changelog/2.1/Breaking-RemoveProjectSpecificCustomFields.rst`.
     */
    private const REMOVED_SETTINGS = [
        'settings.jobs.groupBy',
        'settings.jobs.custom.zuordnung',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate the plugin settings of academic_bite_jobs job lists stored before 2.1.';
    }

    public function getDescription(): string
    {
        return 'Rewrites the view values "ListView", "CardView" and "TableView", and an empty or unknown value, '
            . 'to "List", "Card" or "Table", and removes the settings "settings.jobs.groupBy" and '
            . '"settings.jobs.custom.zuordnung" that version 2.1 removed from the plugin.';
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
        ];
    }

    /**
     * Yields the migrated FlexForm data of every content element that stores a view value
     * which is not exactly a view, or one of the settings version 2.1 removed, keyed by the
     * uid of the content element.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function contentElementsToMigrate(): \Generator
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        // Hidden, time restricted and workspace records are migrated too.
        $queryBuilder->getRestrictions()->removeAll();
        $resultSet = $queryBuilder
            ->select('uid', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in(
                    'CType',
                    $queryBuilder->quoteArrayBasedValueListToStringList(self::CONTENT_TYPES)
                ),
                $queryBuilder->expr()->isNotNull('pi_flexform'),
                $queryBuilder->expr()->neq('pi_flexform', $queryBuilder->createNamedParameter('')),
            )
            ->orderBy('uid')
            ->executeQuery();

        while ($record = $resultSet->fetchAssociative()) {
            $flexFormData = GeneralUtility::xml2array((string)$record['pi_flexform']);
            if (!is_array($flexFormData) || !is_array($flexFormData['data']['sDEF']['lDEF'] ?? null)) {
                continue;
            }
            $changed = false;
            $storedValue = $flexFormData['data']['sDEF']['lDEF'][self::VIEW_FIELD]['vDEF'] ?? '';
            $storedValue = is_string($storedValue) ? $storedValue : '';
            if (ListView::tryFrom($storedValue) === null) {
                $flexFormData['data']['sDEF']['lDEF'][self::VIEW_FIELD]['vDEF'] = ListView::fromStoredValue($storedValue)->value;
                $changed = true;
            }
            foreach (self::REMOVED_SETTINGS as $removedSetting) {
                if (array_key_exists($removedSetting, $flexFormData['data']['sDEF']['lDEF'])) {
                    unset($flexFormData['data']['sDEF']['lDEF'][$removedSetting]);
                    $changed = true;
                }
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
    private function array2xml(array $input): string
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
        $output = GeneralUtility::array2xml($input, '', 0, 'T3FlexForms', 4, $options);

        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . $output;
    }
}
