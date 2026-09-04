<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings;

use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Applies the TCA column configuration of a validation set to a table TCA.
 *
 * @internal not part of public API.
 */
final class TcaValidationMerger
{
    /**
     * Returns `$tableTca` with the `columns.<fieldName>.config` fragments of
     * the set merged in. A missing set leaves the table untouched.
     *
     * @param array<string, mixed> $tableTca
     * @return array<string, mixed>
     */
    public function merge(array $tableTca, ?ValidationSet $validationSet): array
    {
        ArrayUtility::mergeRecursiveWithOverrule($tableTca, $this->toTcaTableConfig($validationSet));
        return $tableTca;
    }

    /**
     * Builds the `columns.<fieldName>.config` fragment of a set. Validations
     * without a TCA configuration contribute nothing, and a set without any
     * produces an empty array rather than an empty `columns` key.
     *
     * @return array<string, mixed>
     */
    public function toTcaTableConfig(?ValidationSet $validationSet): array
    {
        if (!$validationSet instanceof ValidationSet) {
            return [];
        }
        $tableTca = [];
        foreach ($validationSet->validations as $validation) {
            if ($validation->tcaConfig !== []) {
                $tableTca['columns'][$validation->fieldName]['config'] = $validation->tcaConfig;
            }
        }
        return $tableTca;
    }
}
