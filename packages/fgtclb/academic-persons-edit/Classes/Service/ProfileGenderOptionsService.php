<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Service;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


final class ProfileGenderOptionsService
{
    private const TABLE = 'tx_academicpersons_domain_model_profile';
    private const FIELD = 'gender';

    /**
     * @return array<string, string>
     */
    public function getAllowedValues(): array
    {
        $values = [];
        foreach ($this->getConfiguredItems() as $item) {
            $value = (string) ($item['value'] ?? '');
            if ($value === '') {
                // Skip empty string values, handled with `<f:form.select prependOptionLabel="---" />`
                // in the fluid template.
                continue;
            }
            $labelIdentifier = (string)($item['label'] ?? '');
            $translatedLabel = (LocalizationUtility::translate(
                $labelIdentifier,
                'persons_edit',
            ) ?? $labelIdentifier) ?: $labelIdentifier;
            $values[$value] = $translatedLabel;
        }
        return $values;
    }

    public function isAllowed(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        return array_key_exists($value, $this->getAllowedValues());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getConfiguredItems(): array
    {
        $items = $GLOBALS['TCA'][self::TABLE]['columns'][self::FIELD]['config']['items'] ?? [];
        return is_array($items) ? $items : [];
    }
}
