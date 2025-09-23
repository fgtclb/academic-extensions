<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Controller;

use FGTCLB\AcademicJobs\Controller\JobController;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Provides method {@see self::getSelectItemsForTcaManagedTableField()} to be used in extbase action controllers,
 * for example {@see JobController::newAction()} or events to get tca select field items respecting itemUserProc
 * function **and** translating item labels directly.
 *
 * @api considered API and to be used in projects or extension extending academic extensions.
 */
trait GetSelectItemsForTcaManagedTableFieldMethodTrait
{
    /**
     * Determine select items for tca managed table field respecting configured itemUserProc function,
     * translating item labels directly to remove the need to translate them in fluid view when passed
     * into one.
     *
     * See for example {@see JobController::newAction()}.
     *
     * @param string[] $removeItemByValue
     * @return array<int<0, max>, array{
     *      label: string,
     *      value: string,
     *  }>
     * @todo Evaluating TCA in frontend for available options is a hard task to do correctly requiring to execute
     *       TCA item proc functions and so on. It also does not account for eventually FormEngine nodes processing
     *       additional stuff. Current implementation calls the itemProcFunc with a minimal set as context data, but
     *       cannot simulate all the stuff provided by FormEngine.
     * @todo Use TcaSchema for TYPO3 v13, either as dual version OR when dropping TYPO3 v12 support.
     */
    protected function getSelectItemsForTcaManagedTableField(
        ServerRequestInterface $request,
        LocalizationUtility $localizationUtility,
        string $extensionKey,
        string $tableName,
        string $fieldName,
        array $removeItemByValue = [''],
    ): array {
        $currentPageId = $request->getAttribute('frontend.controller')?->id ?? $request->getAttribute('site')?->getRootPageId() ?? 0;
        $items = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['items'] ?? [];
        $itemProcFunc = (string)($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['itemsProcFunc'] ?? '');
        if ($itemProcFunc !== '') {
            $items = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['items'] ?? [];
            $processorParameters = [
                'items' => &$items,
                'config' => $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'],
                'TSconfig' => BackendUtility::getPagesTSconfig($currentPageId),
                'table' => $tableName,
                'field' => $fieldName,
                'effectivePid' => $currentPageId,
                'site' => $request->getAttribute('site'),
            ];
            GeneralUtility::callUserFunction($itemProcFunc, $processorParameters, $this);
            $items = $processorParameters['items'];
        }
        $returnItems = [];
        foreach ($items as $item) {
            $itemValue = (string)($item['value'] ?? '');
            if (in_array($itemValue, $removeItemByValue, true)) {
                // Skip empty string values, handled with `<f:form.select prependOptionLabel="---" />`
                // in the fluid template.
                continue;
            }
            $labelIdentifier = (string)($item['label'] ?? '');
            $returnItems[] = [
                'label' => ($localizationUtility->translate(
                    $labelIdentifier,
                    $extensionKey,
                ) ?? $labelIdentifier) ?: $labelIdentifier,
                'value' => $itemValue,
            ];
        }
        return $returnItems;
    }
}
