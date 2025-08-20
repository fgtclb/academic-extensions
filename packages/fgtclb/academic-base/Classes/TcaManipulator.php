<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides additional methods to manipulate TCA not possible with TYPO3 Core utilities.
 *
 * @internal to be only used by `EXT:academic_*` extensions directly and not part of public API.
 */
final class TcaManipulator
{
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
            if ($part === '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended') {
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
