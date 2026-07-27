<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

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
     * Asserts that the plugin's FlexForm is resolved to actual fields.
     *
     * An empty sheet is the failure mode to watch on TYPO3 v14: `TcaFlexPrepare`
     * swallows an unresolvable data structure there and leaves the caller with
     * `['sheets' => ['sDEF' => []]]`, so the backend renders an empty tab
     * instead of raising anything.
     */
    private function assertPluginFlexFormIsResolved(string $cType, string $sheetName = 'sDEF'): void
    {
        $dataStructure = $this->resolvePluginFlexFormDataStructure($cType);

        self::assertArrayHasKey(
            $sheetName,
            $dataStructure['sheets'] ?? [],
            sprintf('FlexForm of content type "%s" has no sheet "%s".', $cType, $sheetName),
        );
        self::assertNotEmpty(
            $dataStructure['sheets'][$sheetName]['ROOT']['el'] ?? [],
            sprintf(
                'FlexForm sheet "%s" of content type "%s" resolved to no fields at all.',
                $sheetName,
                $cType,
            ),
        );
    }
}
