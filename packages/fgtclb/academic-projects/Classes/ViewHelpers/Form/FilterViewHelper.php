<?php

namespace FGTCLB\AcademicProjects\ViewHelpers\Form;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class FilterViewHelper extends AbstractFormFieldViewHelper
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
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('options', 'array', 'Associative array with internal IDs as key, and the values are displayed in the select box. Can be combined with or replaced by child f:form.select.* nodes.');
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

        $prepend = '';
        $options = '';

        $filter = $this->templateVariableContainer['filter'];
        $selectedCategories = $filter->getFilterCategories();

        if ($this->arguments['options'] instanceof \Traversable) {
            foreach ($this->arguments['options'] as $category) {
                $value = $category->getUid();
                $label = $category->getTitle();

                $isSelected = in_array((string)$value, $selectedCategories);
    
                $option = '<option value="' . htmlspecialchars((string)$value) . '"';
                if ($isSelected) {
                    $option .= ' selected="selected"';
                }
                $option .= '>' . htmlspecialchars((string)$label) . '</option>';
    
                $options .= $option . LF;
            }

            $prepend .= '<option value="0">';
            $prepend .= LocalizationUtility::translate('sys_category.type.' . $category->getType()->__toString(), 'academic_projects');
            $prepend .= '</option>' . LF;
        }

        $this->tag->setContent($prepend . $options);
        $this->tag->forceClosingTag(true);

        $content .= $this->tag->render();
        return $content;
    }
}
