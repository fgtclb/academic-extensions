<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Keeps the stored content type of an existing content element selectable when it
 * is an academic content type that page TSconfig hides on the element's page.
 *
 * Every academic extension hides its content types for the whole installation in
 * its always-loaded page TSconfig, and a component set brings them back per site.
 * On any other page, core `TcaSelectItems` drops the stored type from the row and -
 * because the value was removed by `removeItems` or `keepItems` - deliberately
 * withholds its "invalid value" option. The select then renders its first option
 * as selected, and saving the record silently rewrites its content type.
 *
 * Only the stored value of the record being edited is offered, and only for a type
 * of the `academic` item group. New records and every other content type keep core
 * behaviour. Registered after `TcaSelectItems` in `ext_localconf.php`.
 *
 * @internal Registered as form data provider, not part of the public API.
 */
final readonly class KeepCurrentContentTypeSelectable implements FormDataProviderInterface
{
    private const TABLE_NAME = 'tt_content';
    private const FIELD_NAME = 'CType';
    private const ITEM_GROUP = 'academic';
    private const SUFFIX_LABEL = 'LLL:EXT:academic_base/Resources/Private/Language/locallang_be.xlf:content.ctype.notEnabledOnPage';

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function addData(array $result): array
    {
        if (($result['tableName'] ?? '') !== self::TABLE_NAME || ($result['command'] ?? '') !== 'edit') {
            return $result;
        }
        $contentType = (string)($result['recordTypeValue'] ?? '');
        $items = $result['processedTca']['columns'][self::FIELD_NAME]['config']['items'] ?? null;
        if ($contentType === '' || !is_array($items) || $this->hasItem($items, $contentType)) {
            return $result;
        }
        $fieldTsConfig = $result['pageTsConfig']['TCEFORM.'][self::TABLE_NAME . '.'][self::FIELD_NAME . '.'] ?? [];
        $fieldTsConfig = is_array($fieldTsConfig) ? $fieldTsConfig : [];
        $tcaItem = $this->findAcademicTcaItem($contentType);
        if ($tcaItem === null || !$this->isHiddenByPageTsConfig($fieldTsConfig, $contentType)) {
            return $result;
        }

        $languageService = $this->getLanguageService();
        $altLabel = $fieldTsConfig['altLabels.'][$contentType] ?? '';
        $label = $languageService->sL(trim(is_string($altLabel) && $altLabel !== '' ? $altLabel : (string)($tcaItem['label'] ?? '')));
        $altIcon = $fieldTsConfig['altIcons.'][$contentType] ?? '';
        $description = $tcaItem['description'] ?? null;
        // First, where core puts its "invalid value" option, and in the ungrouped
        // "none" group so no optgroup of its own is rendered for it.
        array_unshift($result['processedTca']['columns'][self::FIELD_NAME]['config']['items'], [
            'label' => sprintf($languageService->sL(self::SUFFIX_LABEL), $label),
            'value' => $contentType,
            'icon' => is_string($altIcon) && $altIcon !== '' ? $altIcon : (($tcaItem['icon'] ?? '') ?: null),
            'group' => 'none',
            'description' => is_string($description) && $description !== '' ? $languageService->sL($description) : $description,
        ]);
        $result['databaseRow'][self::FIELD_NAME] = [$contentType];

        return $result;
    }

    /**
     * @param array<array-key, mixed> $items
     */
    private function hasItem(array $items, string $value): bool
    {
        foreach ($items as $item) {
            if (is_array($item) && (string)($item['value'] ?? '') === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * The TCA item, not the processed one: `TcaSelectItems` has already replaced the
     * processed item list with the items left over after page TSconfig.
     *
     * @return array<string, mixed>|null
     */
    private function findAcademicTcaItem(string $contentType): ?array
    {
        $items = $GLOBALS['TCA'][self::TABLE_NAME]['columns'][self::FIELD_NAME]['config']['items'] ?? [];
        foreach (is_array($items) ? $items : [] as $item) {
            if (is_array($item)
                && (string)($item['value'] ?? '') === $contentType
                && ($item['group'] ?? null) === self::ITEM_GROUP
            ) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Mirrors the two page TSconfig options `TcaSelectItems` removes items with.
     *
     * @param array<array-key, mixed> $fieldTsConfig
     */
    private function isHiddenByPageTsConfig(array $fieldTsConfig, string $contentType): bool
    {
        $removeItems = $fieldTsConfig['removeItems'] ?? null;
        if (is_string($removeItems)
            && in_array($contentType, GeneralUtility::trimExplode(',', $removeItems, true), true)
        ) {
            return true;
        }
        $keepItems = $fieldTsConfig['keepItems'] ?? null;

        return is_string($keepItems)
            && !in_array($contentType, GeneralUtility::trimExplode(',', $keepItems, true), true);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
