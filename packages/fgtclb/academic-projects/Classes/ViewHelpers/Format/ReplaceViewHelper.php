<?php

namespace FGTCLB\AcademicProjects\ViewHelpers\Format;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ReplaceViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('content', 'string', 'Content in which to perform replacement. Array supported.');
        $this->registerArgument('substring', 'string', 'Substring to replace. Array supported.', true);
        $this->registerArgument('replacement', 'string', 'Replacement to insert. Array supported.', false, '');
        $this->registerArgument('caseSensitive', 'boolean', 'If true, perform case-sensitive replacement', false, true);
        $this->registerArgument('returnCount', 'boolean', 'If true, return the number of replacements instead of the replaced content.', false, false);
    }

    /**
     * @return mixed
     */
    public function render(): mixed
    {
        $content = $this->arguments['content'] ?? $this->renderChildren();
        /** @var string|array<string, mixed> $content */
        $content = is_scalar($content) || $content === null ? (string)$content : (array)$content;

        $substring = $this->arguments['substring'];
        /** @var string|array<string, mixed> $substring */
        $substring = is_scalar($substring) ? (string)$substring : (array)$substring;

        $replacement = $this->arguments['replacement'];
        /** @var string|array<string, mixed> $replacement */
        $replacement = is_scalar($replacement) ? (string)$replacement : (array)$replacement;

        $count = 0;
        $caseSensitive = (bool)$this->arguments['caseSensitive'];
        $function = $caseSensitive ? 'str_replace' : 'str_ireplace';
        $replaced = $function($substring, $replacement, $content, $count);

        if ((bool)$this->arguments['returnCount'] === true) {
            return $count;
        }

        return $replaced;
    }
}
