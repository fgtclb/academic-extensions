<?php

namespace FGTCLB\AcademicProjects\ViewHelpers\Form;

use FGTCLB\AcademicProjects\Domain\Enumeration\SortingOptions;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class SortingViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'select';

    /**
     * @var mixed
     */
    protected $selectedValue;

    /**
     * Initialize arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
    }

    /**
     * Render the tag.
     *
     * @return string rendered tag.
     */
    public function render()
    {
        $name = $this->getName();

        $this->tag->addAttribute('name', $name);

        $this->registerFieldNameForFormTokenGeneration($name);
        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        $sortingOptions = [];
        foreach (SortingOptions::getConstants() as $value) {
            
            $label = LocalizationUtility::translate(
                'filter.sorting.' . str_replace(' ', '.', $value),
                'academic_projects'
            );
            $sortingOptions[$value] = $label;
        }

        $content = '';
        foreach ($sortingOptions as $value => $label) {
            $isSelected = $this->isSelected($value);
            $content .= $this->renderOptionTag($value, $label, $isSelected) . LF;
        }

        $this->tag->setContent($content);
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }

    /**
     * @param string $value Value to check for
     * @return bool TRUE if the value should be marked a s selected; FALSE otherwise
     */
    protected function isSelected($value)
    {
        if ((string)$value === $this->getSelectedValue()) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getSelectedValue()
    {
        $this->setRespectSubmittedDataValue(true);

        return $this->getValueAttribute();
    }

    /**
     * @param string $value
     * @param string $label
     * @param bool $isSelected
     * @return string
     */
    protected function renderOptionTag($value, $label, $isSelected)
    {
        $output = '<option value="' . htmlspecialchars((string)$value) . '"';
        if ($isSelected) {
            $output .= ' selected="selected"';
        }
        $output .= '>' . htmlspecialchars((string)$label) . '</option>';
        return $output;
    }
}
