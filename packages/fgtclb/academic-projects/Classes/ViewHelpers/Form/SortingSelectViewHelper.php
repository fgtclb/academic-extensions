<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\ViewHelpers\Form;

use FGTCLB\AcademicProjects\Enumeration\SortingOptions;
use FGTCLB\AcademicProjects\ViewHelpers\Form\AbstractSelectViewHelper;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class SortingSelectViewHelper extends AbstractSelectViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('l10n', 'string', 'If specified, will call the correct label specified in locallang file.');
    }

    /**
     * Render the option tags.
     *
     * @return array an associative array of options, key will be the value of the option tag
     */
    protected function getOptions()
    {
        $optionsArgument = [];

        if (!is_array($this->arguments['options'])
            && !$this->arguments['options'] instanceof \Traversable
        ) {
            foreach (SortingOptions::getConstants() as $value) {
                $label = str_replace(' ', '.', $value);
                $key = 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:flexform.sorting.' . $label;
                $translatedLabel = LocalizationUtility::translate($key);
                if ($translatedLabel !== null) {
                    $label = $translatedLabel;
                }
                $optionsArgument[$value] = $label;
            }
        } else {
            $optionsArgument = $this->arguments['options'];

            if (isset($this->arguments['l10n']) && $this->arguments['l10n']) {
                $request = $this->getRequest();
                $extensionName = $this->arguments['extensionName'] === null ? $request->getControllerExtensionName() : $this->arguments['extensionName'];
            }

            foreach ($optionsArgument as $value => $label) {
                if (isset($this->arguments['l10n']) && $this->arguments['l10n']) {
                    $translatedLabel = LocalizationUtility::translate(
                        $this->arguments['l10n'] . '.' . $label,
                        $extensionName,
                        $this->arguments['arguments']
                    );
                    if ($translatedLabel !== null) {
                        $label = $translatedLabel;
                    }
                }
            }
        }

        $options = [];

        foreach ($optionsArgument as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
                'isSelected' => $this->isSelected($value),
            ];
        }

        if ($this->arguments['sortByOptionLabel'] !== false) {
            usort($options, function ($a, $b) {
                return strcoll($a['label'], $b['label']);
            });
        }

        return $options;
    }

    /**
     * Render the option tags.
     *
     * @param array<string|mixed> $options the options for the form.
     * @return string rendered tags.
     */
    protected function renderOptionTags($options)
    {
        $output = '';
        foreach ($options as $option) {
            $output .= '<option value="' . $option['value'] . '"';
            if ($option['isSelected']) {
                $output .= ' selected="selected"';
            }
            $output .= '>' . htmlspecialchars((string)$option['label']) . '</option>' . LF;
        }
        return $output;
    }
}
