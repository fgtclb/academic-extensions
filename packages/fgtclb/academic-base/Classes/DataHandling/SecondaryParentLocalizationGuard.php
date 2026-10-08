<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\DataHandling;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Removes the children a localized parent drags along through an inline relation it
 * does not own (ACE-874).
 *
 * Several records list children that belong to another record: a contacts role lists
 * the page contacts that hold it, a partner role the partnerships, an organisational
 * unit the contracts. The child is translated with the record that owns it, the page
 * or the profile, and keeps the second parent of its default language record.
 *
 * `DataHandler::localize()` copies every inline child of a localized record, and no
 * TCA option prevents that: `copyRecord_processRelation()` calls `localize()` for each
 * child, on TYPO3 v13 and v14 alike. For such a second parent that creates a
 * translation of every child it lists, attached to the parent translation, and, for a
 * child that is translated or in another language already, one more copy of it. A
 * profile then shows a contract twice, a page a contact twice.
 *
 * The guard runs in `processCmdmap_afterFinish`, after `remapListedDBRecords()` wired
 * the created children to the created parent. `DataHandler::$copyMappingArray_merged`
 * holds every record the run created as `source uid => new uid`. A created child of
 * the relation that points at a parent created in another language than its source,
 * by `localize` or `copyToLanguage`, is deleted for good, with its own inline
 * children, through `DataHandler::deleteAction()` of the running DataHandler with a
 * forced hard delete, so no record is left behind. The system log and the history
 * keep the creation and the deletion. Its reference index and cache are updated with
 * the rest of the run, and in a workspace the new record is discarded. The inline
 * column of the parent, which counts its children, is set to the children it still
 * has. A plain copy of the parent is left alone.
 *
 * TYPO3 v14 offers `BeforeRemoveNonCopyableFieldsEvent`, which could keep the field
 * out of the localization altogether. v13 has nothing comparable, so the guard is the
 * one mechanism for both versions.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
final readonly class SecondaryParentLocalizationGuard
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
        private ConnectionPool $connectionPool,
    ) {}

    public function removeChildrenLocalizedWithParent(DataHandler $dataHandler, string $parentTable, string $field): void
    {
        $createdParents = $dataHandler->copyMappingArray_merged[$parentTable] ?? [];
        if ($createdParents === [] || !$this->tcaSchemaFactory->has($parentTable)) {
            return;
        }
        $parentSchema = $this->tcaSchemaFactory->get($parentTable);
        if (!$parentSchema->hasField($field) || !$parentSchema->isLanguageAware()) {
            return;
        }
        $configuration = $parentSchema->getField($field)->getConfiguration();
        $childTable = (string)($configuration['foreign_table'] ?? '');
        $foreignField = (string)($configuration['foreign_field'] ?? '');
        $createdChildren = $childTable !== '' ? $dataHandler->copyMappingArray_merged[$childTable] ?? [] : [];
        if ($foreignField === '' || $createdChildren === []) {
            return;
        }
        $languageField = $parentSchema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();

        $localizedParents = [];
        foreach ($createdParents as $sourceUid => $createdUid) {
            $source = BackendUtility::getRecord($parentTable, (int)$sourceUid);
            $created = BackendUtility::getRecord($parentTable, (int)$createdUid);
            if ($source === null || $created === null
                || (int)$created[$languageField] === (int)$source[$languageField]
            ) {
                continue;
            }
            $localizedParents[(int)$createdUid] = true;
        }
        if ($localizedParents === []) {
            return;
        }

        foreach ($createdChildren as $sourceUid => $createdUid) {
            // Deleted ones too: another hook of the run may have deleted a created child
            // already, the guard removes it for good.
            $child = BackendUtility::getRecord($childTable, (int)$createdUid, '*', '', false);
            if ($child === null
                // A workspace version of the source rather than a copy of it.
                || (int)($child['t3ver_oid'] ?? 0) === (int)$sourceUid
                || !isset($localizedParents[(int)$child[$foreignField]])
            ) {
                continue;
            }
            $dataHandler->deleteAction($childTable, (int)$createdUid, false, true);
            $localizedParents[(int)$child[$foreignField]] = false;
        }

        // The remap of the run counted the removed children into the inline column.
        foreach ($localizedParents as $parentUid => $untouched) {
            if ($untouched) {
                continue;
            }
            $this->connectionPool->getConnectionForTable($parentTable)->update(
                $parentTable,
                [$field => $this->countChildren($childTable, $foreignField, $parentUid)],
                ['uid' => $parentUid],
            );
        }
    }

    private function countChildren(string $childTable, string $foreignField, int $parentUid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($childTable);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return (int)$queryBuilder
            ->count('uid')
            ->from($childTable)
            ->where($queryBuilder->expr()->eq($foreignField, $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
    }
}
