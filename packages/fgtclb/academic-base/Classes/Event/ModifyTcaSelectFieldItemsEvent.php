<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Event;

use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

/**
 * Shared event to be dispatched in itemsProcFunc for select fields, TCA and/or flexform,
 * provided by academic extensions, for example:
 *
 * - {@see \FGTCLB\AcademicJobs\Backend\FormEngine\EmploymentTypeItems::itemsProcFunc()}
 * - {@see \FGTCLB\AcademicJobs\Backend\FormEngine\TypeItems::itemsProcFunc()}
 *
 * The parameters are the ones of an itemsProcFunc, declared once as the type alias
 * `ItemsProcFuncParameters`, which the item providers import. They hold what TYPO3
 * passes on v12, v13 and v14:
 *
 * - `items` holds `SelectItem` objects for the items TYPO3 hands to the provider, and
 *   arrays for the items a provider adds and for every item the job form passes through
 *   {@see \FGTCLB\AcademicBase\Controller\GetSelectItemsForTcaManagedTableFieldMethodTrait}.
 * - `row` is optional because an item provider is also called outside FormEngine,
 *   where no record exists:
 *   {@see \FGTCLB\AcademicBase\Controller\GetSelectItemsForTcaManagedTableFieldMethodTrait}
 *   passes an empty array, and another caller may leave the key out.
 * - `site` is a `NullSite` for a record outside any site, on page 0 for example, and
 *   `null` when the caller has no site: the default of `FormDataCompiler` on v12 and v13,
 *   or a request without a site attribute in the job form.
 * - `inlineParentUid` and `inlineTopMostParentUid` are `''` for a record that is not
 *   edited as an inline child, and a `NEW...` string for a parent that is not saved yet.
 *   The site configuration passes `null` as `inlineTopMostParentUid`.
 * - The inline keys and `flexParentDatabaseRow` are missing outside FormEngine.
 *
 * @phpstan-type ItemsProcFuncParameters array{
 *      items: array<int, SelectItem|array{
 *       label?: string|null,
 *       value?: mixed,
 *       icon?: string|null,
 *       group?: string|null,
 *      }>,
 *      config: array<string, mixed>,
 *      TSconfig: array<string, mixed>|null,
 *      table: string,
 *      row?: array<string, mixed>,
 *      field: string,
 *      effectivePid: int,
 *      site: SiteInterface|null,
 *      flexParentDatabaseRow?: array<string, mixed>|null,
 *      inlineParentUid?: int|string,
 *      inlineParentTableName?: string,
 *      inlineParentFieldName?: string,
 *      inlineParentConfig?: array<string, mixed>,
 *      inlineTopMostParentUid?: int|string|null,
 *      inlineTopMostParentTableName?: string,
 *      inlineTopMostParentFieldName?: string,
 *  }
 *
 * @api
 */
final class ModifyTcaSelectFieldItemsEvent
{
    /**
     * @param ItemsProcFuncParameters $parameters
     */
    public function __construct(
        private array $parameters,
    ) {}

    /**
     * @link https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/ItemsProcFunc.html#passed-parameters
     * @return ItemsProcFuncParameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param ItemsProcFuncParameters $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}
