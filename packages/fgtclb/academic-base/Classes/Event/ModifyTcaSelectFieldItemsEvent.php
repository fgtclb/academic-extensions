<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Event;

use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Shared event to be dispatched in itemsProcFunc for select fields, TCA and/or flexform,
 * provided by academic extensions, for example:
 *
 * - {@see \FGTCLB\AcademicJobs\Backend\FormEngine\EmploymentTypeItems::itemsProcFunc()}
 * - {@see \FGTCLB\AcademicJobs\Backend\FormEngine\TypeItems::itemsProcFunc()}
 */
final class ModifyTcaSelectFieldItemsEvent
{
    /**
     * @param array{
     *     items: array<int, array{
     *      label?: string|null,
     *      value?: mixed,
     *      icon?: string|null,
     *      group?: string|null,
     *     }>,
     *     config: array<string, mixed>,
     *     TSconfig: array<string, mixed>,
     *     table: string,
     *     row: array<string, mixed>,
     *     field: string,
     *     effectivePid: int,
     *     site: Site|null,
     *     flexParentDatabaseRow?: array<string, mixed>|null,
     *     inlineParentUid?: int,
     *     inlineParentTableName?: string,
     *     inlineParentFieldName?: string,
     *     inlineParentConfig?: array<string, mixed>,
     *     inlineTopMostParentUid?: int,
     *     inlineTopMostParentTableName?: string,
     *     inlineTopMostParentFieldName?: string,
     * } $parameters
     */
    public function __construct(
        private array $parameters,
    ) {}

    /**
     * @link https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/ItemsProcFunc.html#passed-parameters
     * @return array{
     *      items: array<int, array{
     *       label?: string|null,
     *       value?: mixed,
     *       icon?: string|null,
     *       group?: string|null,
     *      }>,
     *      config: array<string, mixed>,
     *      TSconfig: array<string, mixed>,
     *      table: string,
     *      row: array<string, mixed>,
     *      field: string,
     *      effectivePid: int,
     *      site: Site|null,
     *      flexParentDatabaseRow?: array<string, mixed>|null,
     *      inlineParentUid?: int,
     *      inlineParentTableName?: string,
     *      inlineParentFieldName?: string,
     *      inlineParentConfig?: array<string, mixed>,
     *      inlineTopMostParentUid?: int,
     *      inlineTopMostParentTableName?: string,
     *      inlineTopMostParentFieldName?: string,
     *  }
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array{
     *      items: array<int, array{
     *       label?: string|null,
     *       value?: mixed,
     *       icon?: string|null,
     *       group?: string|null,
     *      }>,
     *      config: array<string, mixed>,
     *      TSconfig: array<string, mixed>,
     *      table: string,
     *      row: array<string, mixed>,
     *      field: string,
     *      effectivePid: int,
     *      site: Site|null,
     *      flexParentDatabaseRow?: array<string, mixed>|null,
     *      inlineParentUid?: int,
     *      inlineParentTableName?: string,
     *      inlineParentFieldName?: string,
     *      inlineParentConfig?: array<string, mixed>,
     *      inlineTopMostParentUid?: int,
     *      inlineTopMostParentTableName?: string,
     *      inlineTopMostParentFieldName?: string,
     *  } $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}
