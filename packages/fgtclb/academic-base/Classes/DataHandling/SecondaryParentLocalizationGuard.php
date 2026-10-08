<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\DataHandling;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * child, on TYPO3 v12 and v13 alike. For such a second parent that creates a
 * translation of every child it lists, attached to the parent translation, and, for a
 * child that is translated or in another language already, one more copy of it. A
 * profile then shows a contract twice, a page a contact twice.
 *
 * The guard runs in `processCmdmap_afterFinish`, after `remapListedDBRecords()` wired
 * the created children to the created parent. `DataHandler::$copyMappingArray_merged`
 * holds every record the run created as `source uid => new uid`. A created child of
 * the relation that points at a parent created in another language than its source,
 * by `localize` or `copyToLanguage`, is deleted with a nested DataHandler `delete`
 * command, which takes its own inline children along and, in a workspace, discards
 * the new record. A plain copy of the parent is left alone.
 *
 * The delete is a soft one. A forced hard delete of TYPO3 v12 leaves the inline
 * children of the deleted record behind, so it cannot be used on both versions.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
final class SecondaryParentLocalizationGuard
{
    public function removeChildrenLocalizedWithParent(DataHandler $dataHandler, string $parentTable, string $field): void
    {
        $createdParents = $dataHandler->copyMappingArray_merged[$parentTable] ?? [];
        $parentControl = $GLOBALS['TCA'][$parentTable]['ctrl'] ?? [];
        $configuration = $GLOBALS['TCA'][$parentTable]['columns'][$field]['config'] ?? [];
        $languageField = (string)($parentControl['languageField'] ?? '');
        $childTable = (string)($configuration['foreign_table'] ?? '');
        $foreignField = (string)($configuration['foreign_field'] ?? '');
        $createdChildren = $childTable !== '' ? $dataHandler->copyMappingArray_merged[$childTable] ?? [] : [];
        if (!is_array($createdParents) || $createdParents === []
            || $languageField === '' || $foreignField === ''
            || !is_array($createdChildren) || $createdChildren === []
        ) {
            return;
        }

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

        $commandMap = [];
        foreach ($createdChildren as $sourceUid => $createdUid) {
            $child = BackendUtility::getRecord($childTable, (int)$createdUid);
            if ($child === null
                // A workspace version of the source rather than a copy of it.
                || (int)($child['t3ver_oid'] ?? 0) === (int)$sourceUid
                || !isset($localizedParents[(int)$child[$foreignField]])
            ) {
                continue;
            }
            $commandMap[$childTable][(int)$createdUid]['delete'] = 1;
        }
        if ($commandMap === []) {
            return;
        }

        // The nested run reuses the acting backend user, so permissions and the workspace
        // dispatch of the delete apply unchanged.
        $remover = GeneralUtility::makeInstance(DataHandler::class);
        $remover->enableLogging = $dataHandler->enableLogging;
        $remover->start([], $commandMap, $dataHandler->BE_USER);
        $remover->process_cmdmap();
        if ($remover->errorLog !== []) {
            $dataHandler->errorLog = array_merge($dataHandler->errorLog, $remover->errorLog);
        }
    }
}
