<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\ViewHelpers;

use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Hands the markup of a set of icons of the frontend icon registry to the frontend
 * TypeScript of a page, without a request: a JSON data block the module reads, keyed by
 * identifier.
 *
 * Usage, with the namespace declared as
 * `xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"`:
 *
 * ::
 *
 *      <ab:frontendIconMap identifiers="{0: 'tx-academicbase-action-add', 1: 'tx-academicbase-action-delete'}" />
 *
 * renders, shortened
 *
 * ::
 *
 *      <script type="application/json" data-academic-icons data-academic-icons-size="small">
 *          {"tx-academicbase-action-add":"\u003Cspan class=\u0022t3js-icon …\u0022 …\u003E…\u003C/span\u003E", …}
 *      </script>
 *
 * on one line. `JSON.parse()` returns the plain markup.
 *
 * The markup is what `<ab:icon … alternativeMarkupIdentifier="inline" />` renders,
 * through {@see FrontendIconRenderer}: an identifier the frontend icon registry does not
 * know, the `default-not-found` placeholder itself and an icon that is not inlined are
 * left out of the map rather than rendered as the placeholder.
 *
 * Neither the element nor its content is executed, so the page's Content Security
 * Policy needs nothing for it: a `<script>` with a JSON type is a data block. The JSON
 * is encoded with `<`, `>`, `&` and both quotes escaped, so no markup inside it can end
 * the element.
 *
 * @internal not part of public API. The tag name and its arguments are, and so is the
 *           JSON it renders.
 */
final class FrontendIconMapViewHelper extends AbstractViewHelper
{
    /**
     * The markup of the element is built here and must reach the page unescaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly FrontendIconRenderer $frontendIconRenderer,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('identifiers', 'array', 'The icon identifiers to hand over', true);
        $this->registerArgument('size', 'string', 'The icon size: default, small, medium, large or mega', false, 'small');
    }

    public function render(): string
    {
        $sizeName = (string)($this->arguments['size'] ?? 'small');
        $size = FrontendIconRenderer::sizeFrom($sizeName);
        if ($size === null) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not an icon size. Use default, small, medium, large or mega.', $sizeName),
                1789120001,
            );
        }
        $identifiers = $this->arguments['identifiers'] ?? [];
        $map = $this->frontendIconRenderer->render(is_iterable($identifiers) ? $identifiers : [], $size);

        $attributes = [
            'type' => 'application/json',
            'data-academic-icons' => '',
            'data-academic-icons-size' => $size->value,
        ];

        $markup = '<script';
        foreach ($attributes as $name => $value) {
            $markup .= $value === '' ? ' ' . $name : sprintf(' %s="%s"', $name, htmlspecialchars($value));
        }

        // An icon whose markup is not valid UTF-8 degrades to U+FFFD rather than
        // failing the whole content element.
        return $markup . '>' . json_encode(
            $map,
            JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        ) . '</script>';
    }
}
