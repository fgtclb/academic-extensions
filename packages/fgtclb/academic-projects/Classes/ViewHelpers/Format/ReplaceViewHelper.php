<?php

namespace FGTCLB\AcademicProjects\ViewHelpers\Format;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

class ReplaceViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('content', 'string', 'Content in which to perform replacement. Array supported.');
        $this->registerArgument('substring', 'string', 'Substring to replace. Array supported.', true);
        $this->registerArgument('replacement', 'string', 'Replacement to insert. Array supported.', false, '');
        $this->registerArgument('caseSensitive', 'boolean', 'If true, perform case-sensitive replacement', false, true);
    }

    /**
     * @return array|string|int
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $content = $renderChildrenClosure();
        /** @var string|array $content */
        $content = is_scalar($content) || $content === null ? (string)$content : (array)$content;

        $substring = $arguments['substring'];
        /** @var string|array $substring */
        $substring = is_scalar($substring) ? (string)$substring : (array)$substring;

        $replacement = $arguments['replacement'];
        /** @var string|array $replacement */
        $replacement = is_scalar($replacement) ? (string)$replacement : (array)$replacement;

        $count = 0;
        $caseSensitive = (bool)$arguments['caseSensitive'];
        $function = $caseSensitive ? 'str_replace' : 'str_ireplace';
        $replaced = $function($substring, $replacement, $content, $count);
        if ($arguments['returnCount'] ?? false) {
            return $count;
        }
        return $replaced;
    }
}
