<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

/**
 * Provides additional methods to manipulate TCA not possible with TYPO3 Core utilities.
 *
 * @internal to be only used by `EXT:academic_*` extensions directly and not part of public API.
 */
final class TcaManipulator
{
    /**
     * The "extended" tab, in both spellings that can occur in a `showitem` string.
     *
     * TYPO3 v14 moved the core TCA `showitem` definitions to short form label
     * references (breaking #107789): `core.form.tabs:extended` instead of the
     * full `LLL:EXT:core/...` path. TYPO3 v14.3 core `pages` TCA contains no
     * occurrence of the long form at all, so matching only that form silently
     * stops detecting the tab there.
     *
     * Both forms have to be *recognised*. Only `EXTENDED_TAB` is ever *written*:
     * `EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf` still exists
     * in TYPO3 v14 and still carries the `extended` label, so the long form
     * resolves on both supported core versions, while the short form does not
     * resolve on TYPO3 v13.
     *
     * @todo typo3/cms-core >=14 Emit `EXTENDED_TAB_SHORT` and drop the long form
     *       once TYPO3 v13 support is removed.
     */
    private const EXTENDED_TAB = '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended';
    private const EXTENDED_TAB_SHORT = '--div--;core.form.tabs:extended';

    /**
     * @return string[]
     */
    private function extendedTabVariants(): array
    {
        return [self::EXTENDED_TAB, self::EXTENDED_TAB_SHORT];
    }

    /**
     * Convenience method so you don't have to deal with strings and arrays and $GLOBALS[TCA] directly that much.
     *
     * Adds a new entry to an existing TCA DB table that has a type field configured (via $TCA[$table][ctrl][type])
     * such as "tt_content" or "pages" tables.
     *
     * Takes the $item (label, value[, icon] etc.) and adds the item to the items-array of $TCA[$table]
     * of the "type" field. The position in the list can be chosen via the $position argument.
     *
     * In addition, a type-icon gets registered, and, based on the $item[value], the record type is also added
     * to $TCA[$table]['types'][$newType], where $showItemList is added as 'showitem' key, as well as $additionalTypeInformation
     * such as 'columnsOverride' or 'creationOptions'.
     *
     * In addition, the $showItemList will receive a 'extended' tab at the very end, so other extensions
     * that add additional fields, will receive this at the extended tab automatically.
     *
     * Can be used in favor of addPlugin() and addTcaSelectItem().
     *
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param array{label: string, value: int|string|null, description?: string|string[]|null, icon?: string|null, group?: string|null}|SelectItem $item The item to add to the select field
     * @param string $showItemList A string containing all fields to be used / displayed in this type
     * @param array<string, mixed> $additionalTypeInformation Additional type information to be added to the type in $TCA[$table]['types']
     * @param string $position The position in the list where the new item should be added, something like "after:textpic"
     * @param string $table The table name, defaults to 'tt_content'
     *
     * @todo typo3/cms >13.4 Replace usages with `ExtensionManagementUtility::addRecordType()`
     *       when TYPO3 v12 support is removed and remove this method here.
     */
    public function addRecordType(array|SelectItem $item, string $showItemList, array $additionalTypeInformation = [], string $position = '', string $table = 'tt_content'): void
    {
        $selectItem = is_array($item) ? SelectItem::fromTcaItemArray($item) : $item;
        $typeField = $GLOBALS['TCA'][$table]['ctrl']['type'] ?? null;
        // Throw exception if no type is set
        if ($typeField === null) {
            throw new \RuntimeException('Cannot add record type "' . $selectItem->getValue() . '" for TCA table "' . $table . '" without type field defined.', 1725997543);
        }
        // Set the type icon as well
        if ($selectItem->getIcon()) {
            $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$selectItem->getValue()] = $selectItem->getIcon();
        }
        if (!$selectItem->hasGroup()) {
            $selectItem = $selectItem->withGroup('default');
        }
        $relativeInformation = GeneralUtility::trimExplode(':', $position, true, 2);
        ExtensionManagementUtility::addTcaSelectItem($table, $typeField, $selectItem->toArray(), $relativeInformation[0] ?? '', $relativeInformation[1] ?? '');
        $showItemList = trim($showItemList, ', ');
        // Add the extended tab if not already added manually at the very end.
        // Both spellings count as "already added", otherwise a caller using the
        // TYPO3 v14 short form would end up with two extended tabs.
        $hasExtendedTab = false;
        foreach ($this->extendedTabVariants() as $extendedTab) {
            if (str_contains($showItemList, $extendedTab)) {
                $hasExtendedTab = true;
                break;
            }
        }
        if ($showItemList !== '' && !$hasExtendedTab) {
            $showItemList .= ',' . self::EXTENDED_TAB;
        }
        if ($showItemList !== '') {
            $showItemList .= ',';
        }
        $additionalTypeInformation['showitem'] = $showItemList;
        $GLOBALS['TCA'][$table]['types'][$selectItem->getValue()] = $additionalTypeInformation;
    }

    /**
     * Register an Extbase plugin as a content element (CType) in a way that works
     * on both TYPO3 v13 and v14.
     *
     * TYPO3 v13 `ExtensionManagementUtility::addPlugin()` takes
     * `($item, $type, $extensionKey)` and only registers a CType when
     * `$type === ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT`. TYPO3 v14 removed
     * the `list_type` sub-type concept: `addPlugin()` now takes `($item, $flexForm)`
     * and always registers the plugin value as a first-class CType, so passing the
     * old `$type`/`$extensionKey` arguments is both wrong and a signature error.
     *
     * The arguments are collected in an array and spread so the version-specific
     * argument count is resolved at runtime (and not flagged by static analysis).
     *
     * FOR USE IN files in `Configuration/TCA/Overrides/*.php`.
     *
     * @param array{label: string, value: int|string|null, description?: string|string[]|null, icon?: string|null, group?: string|null}|SelectItem $item
     */
    public function addContentElementPlugin(array|SelectItem $item, string $extensionKey): void
    {
        $arguments = [$item];
        if ((new Typo3Version())->getMajorVersion() < 14) {
            $arguments[] = ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT;
            $arguments[] = $extensionKey;
        }
        ExtensionManagementUtility::addPlugin(...$arguments);
    }

    /**
     * Assign the FlexForm data structure of a plugin content element in a way
     * that works on both TYPO3 v13 and v14.
     *
     * The two core versions want a different shape and neither tolerates the
     * other, which makes this the one place a version switch is unavoidable:
     *
     * * TYPO3 v13 resolves `ds` through `ds_pointerField` and requires an array.
     *   Given a string it throws, and the whole content element can no longer be
     *   opened in the backend (`FlexFormTools`, code 1463826960).
     * * TYPO3 v14 resolves the data structure through the record type of the TCA
     *   schema and requires the string. Given an array it throws
     *   `InvalidTcaException` (code 1751796940) — which `TcaFlexPrepare` catches,
     *   so the backend silently renders an **empty** FlexForm tab instead of
     *   failing visibly.
     *
     * FOR USE IN files in `Configuration/TCA/Overrides/*.php`.
     *
     * @param string $cType Content element type the data structure belongs to
     * @param string $dataStructure Data structure reference, e.g. `FILE:EXT:my_ext/Configuration/FlexForms/List.xml`
     */
    public function addContentElementPluginFlexForm(string $cType, string $dataStructure): void
    {
        $GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides']['pi_flexform']['config']['ds']
            = (new Typo3Version())->getMajorVersion() >= 14
                ? $dataStructure
                : ['default' => $dataStructure];
    }

    /**
     * Add `$definitionToAdd` as last item to the general tab for page types.
     *
     * FOR USE IN files in `Configuration/TCA/Overrides/*.php`.
     *
     * @param array<string, mixed> $tca
     * @param string $definitionToAdd
     * @param list<int|string> $types Only for selected types
     * @param list<int|string> $excludeTypes Don't set for these types, overruling if set in `$types`.
     * @return array<string, mixed>
     */
    public function addToPageTypesGeneralTab(
        array $tca,
        string $definitionToAdd,
        array $types = [],
        array $excludeTypes = [],
    ): array {
        if (trim($definitionToAdd, ';, ') === '') {
            // Nothing to add, return early.
            return $tca;
        }
        if (!isset($tca['pages']['types'])
            || !is_array($tca['pages']['types'])
        ) {
            // No types defined. Nothing to do.
            return $tca;
        }
        foreach ($tca['pages']['types'] as $type => &$typeConfig) {
            if ($types !== [] && !in_array($type, $types, true)) {
                // Type is not selected to be manipulated, skip.
                continue;
            }
            if ($excludeTypes !== [] && in_array($type, $excludeTypes, true)) {
                // Skip $type and continue with next type.
                continue;
            }
            // Split by item separator (`,`)
            $showItemSplitted = GeneralUtility::trimExplode(',', $typeConfig['showitem'] ?? '', true);
            // Extract all fields of the extended tab to add it at the end
            [$showItemList, $extendedParts] = $this->extractExtendedParts($showItemSplitted);
            // Because FormEngine will add the general tab automatically, we will not do this here
            // However, if the first item in the $showItemList is actually a tab (--div--), we need to
            // add if before the "first fields"
            $showItemParts = [];
            if (str_starts_with($showItemList[0] ?? '', '--div--')) {
                $showItemParts[] = $showItemList[0];
                unset($showItemList[0]);
            }
            // Copy over item parts to array, adding $definitionToAdd to last item on the general tab
            $newShowItemParts = [];
            $added = false;
            foreach ($showItemList as $itemPart) {
                if (!$added && str_starts_with($itemPart, '--div--')) {
                    $newShowItemParts[] = trim($definitionToAdd, ',');
                    $added = true;
                }
                $newShowItemParts[] = $itemPart;
            }
            // If no dedicated tab (div) has been declared and thus the definition not added yet, add it now
            // to ensure it is added to the first (general) tab before readding the extended parts later on.
            if (!$added) {
                $newShowItemParts[] = trim($definitionToAdd, ',');
            }
            // Add first diff again (general tab)
            $newShowItemParts = array_merge($showItemParts, $newShowItemParts);
            // Add extended tab at the end - if it exists
            $newShowItemParts = array_merge($newShowItemParts, $extendedParts);
            $newShowItemParts[] = '';
            // Merge parts together and set it as type showitem definition
            $typeConfig['showitem'] = str_replace(
                [
                    '--div--',
                    '--palette--',
                ],
                [
                    LF . '--div--',
                    LF . '--palette--',
                ],
                trim(implode(',', $newShowItemParts)),
            );
        }
        // cleanup reference
        unset($typeConfig);
        return $tca;
    }

    /**
     * @param array<int, string> $showItemFiltered
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function extractExtendedParts(array $showItemFiltered): array
    {
        $extendedParts = [];
        $addFields = false;
        foreach ($showItemFiltered as $key => $part) {
            if (in_array($part, $this->extendedTabVariants(), true)) {
                $extendedParts[] = $part;
                $addFields = true;
                unset($showItemFiltered[$key]);
            } elseif ($addFields) {
                if (str_starts_with($part, '--div--')) {
                    break;
                }
                $extendedParts[] = $part;
                unset($showItemFiltered[$key]);
            }
        }
        return [$showItemFiltered, $extendedParts];
    }
}
