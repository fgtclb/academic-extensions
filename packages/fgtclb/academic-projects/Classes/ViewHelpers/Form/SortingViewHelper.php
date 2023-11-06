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
        $this->setRespectSubmittedDataValue(true);

        $options = '';
        foreach (SortingOptions::getConstants() as $value) {

            $label = LocalizationUtility::translate(
                'filter.sorting.' . str_replace(' ', '.', $value),
                'academic_projects'
            );

            $isSelected = (string)$value === $this->getValueAttribute();

            $option = '<option value="' . htmlspecialchars((string)$value) . '"';
            if ($isSelected) {
                $option .= ' selected="selected"';
            }
            $option .= '>' . htmlspecialchars((string)$label) . '</option>';

            $options .= $option . LF;
        }

        $this->tag->setContent($options);
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
