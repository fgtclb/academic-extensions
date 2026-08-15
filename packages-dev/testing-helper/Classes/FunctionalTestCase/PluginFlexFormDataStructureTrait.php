<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolves the FlexForm data structure of a plugin content type the way
 * FormEngine does when the record is opened in the backend.
 *
 * The two data providers below are the first ones in the FormEngine chain that
 * touch `pi_flexform`: `TcaColumnsOverrides` merges the type specific
 * `columnsOverrides` into the columns, and `TcaFlexPrepare` resolves the `ds`
 * reference into the parsed data structure. Everything that can go wrong with a
 * plugin data structure goes wrong in one of those two, which is why the
 * assertion runs through them instead of through a full `FormDataCompiler` —
 * no backend user, no page tree and no request are needed.
 *
 * The defect this exists for: assigning the data structure as a plain string
 * (`'FILE:EXT:…'`) is a TYPO3 v14 idiom. TYPO3 v13 requires an array and
 * `FlexFormTools` throws, so the whole record fails to open — while a v14 run
 * stays perfectly green.
 *
 * @see https://docs.typo3.org/permalink/changelog:breaking-107945-1750669512
 */
trait PluginFlexFormDataStructureTrait
{
    /**
     * @return array<string, mixed> The resolved data structure, as FormEngine would render it
     */
    private function resolvePluginFlexFormDataStructure(string $cType): array
    {
        $result = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'recordTypeValue' => $cType,
            'userTsConfig' => [],
            'databaseRow' => [
                'uid' => 1,
                'pid' => 1,
                'CType' => $cType,
                // TYPO3 v13 resolves the data structure through the
                // `ds_pointerField` "list_type,CType" and needs the field to be
                // present in the row; TYPO3 v14 dropped the column entirely.
                'list_type' => '',
                'pi_flexform' => '',
            ],
            'processedTca' => $GLOBALS['TCA']['tt_content'],
        ];
        if (class_exists(TcaSchemaFactory::class)) {
            // TYPO3 v14 resolves the data structure through the record type of
            // the table schema. This is what FormEngine's InitializeProcessedTca
            // puts into the result.
            $result['tcaSchemata'] = $this->get(TcaSchemaFactory::class)->all();
        }

        $result = $this->get(TcaColumnsOverrides::class)->addData($result);
        $result = $this->get(TcaFlexPrepare::class)->addData($result);

        return $result['processedTca']['columns']['pi_flexform']['config']['ds'] ?? [];
    }

    /**
     * Asserts that the plugin's own FlexForm is resolved to actual fields.
     *
     * Two failure modes, one per core version, and the assertion has to cover
     * both because each one is silent in the backend:
     *
     * * TYPO3 v14 leaves an unresolvable data structure as
     *   `['sheets' => ['sDEF' => []]]` — `TcaFlexPrepare` swallows the exception
     *   and the backend renders an empty tab.
     * * TYPO3 v13 falls back to **core's own default** data structure when the
     *   extension's one is registered where core does not look for it. The
     *   backend then shows a foreign field instead of the plugin options, which
     *   a "did anything resolve at all" assertion happily accepts — that is how
     *   the defect behind ACE-387 passed CI for seven extensions.
     */
    private function assertPluginFlexFormIsResolved(string $cType, string $sheetName = 'sDEF'): void
    {
        $dataStructure = $this->resolvePluginFlexFormDataStructure($cType);

        self::assertArrayHasKey(
            $sheetName,
            $dataStructure['sheets'] ?? [],
            sprintf('FlexForm of content type "%s" has no sheet "%s".', $cType, $sheetName),
        );
        $resolvedFields = array_keys($dataStructure['sheets'][$sheetName]['ROOT']['el'] ?? []);
        self::assertNotEmpty(
            $resolvedFields,
            sprintf(
                'FlexForm sheet "%s" of content type "%s" resolved to no fields at all.',
                $sheetName,
                $cType,
            ),
        );

        $coreDefaultFields = $this->getCoreDefaultFlexFormFields();
        if ($coreDefaultFields !== []) {
            self::assertNotSame(
                $coreDefaultFields,
                $resolvedFields,
                sprintf(
                    'FlexForm of content type "%s" resolved to the TYPO3 core default data structure'
                    . ' instead of the one the extension registers.',
                    $cType,
                ),
            );
        }
    }

    /**
     * The field names of the data structure core ships in
     * `tt_content.pi_flexform`, which is what a plugin falls back to when its
     * own one is not found. Read from the TCA rather than hard coded, so a
     * changed core default cannot turn this guard into a no-op.
     *
     * @return list<string>
     */
    private function getCoreDefaultFlexFormFields(): array
    {
        $coreDefault = $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['default'] ?? null;
        if (!is_string($coreDefault) || $coreDefault === '') {
            // TYPO3 v14 has no such fallback.
            return [];
        }

        $parsed = GeneralUtility::xml2array($coreDefault);

        return is_array($parsed) ? array_keys($parsed['ROOT']['el'] ?? []) : [];
    }
}
