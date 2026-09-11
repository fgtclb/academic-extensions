<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\ViewHelpers;

use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Hands the markup of a set of icons to the frontend TypeScript of a page, without a
 * request: a JSON data block the module reads, keyed by identifier.
 *
 * Usage, with the namespace declared as
 * `xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"`:
 *
 * ::
 *
 *      <ab:frontendIconMap identifiers="{0: 'tx-academicbase-action-add', 1: 'tx-academicbase-action-delete'}" />
 *
 * renders
 *
 * ::
 *
 *      <script type="application/json" data-academic-icons data-academic-icons-size="small">
 *          {"tx-academicbase-action-add": "<span class=\"t3js-icon …\">…</span>", …}
 *      </script>
 *
 * The markup is what `<core:icon … alternativeMarkupIdentifier="inline" />` renders,
 * through {@see FrontendIconRenderer}: an identifier that is not registered, not
 * allowed for the frontend or deprecated is left out of the map rather than rendered
 * as the `default-not-found` placeholder.
 *
 * With `endpoint="1"` the element also names the icon endpoint of the current site
 * (`data-academic-icons-url`) and the version token of the icon set
 * (`data-academic-icons-version`), so a module that asks for an icon the template did
 * not name can fetch it. Outside a site there is no endpoint, and both are omitted.
 *
 * Neither the element nor its content is executed, so the page's Content Security
 * Policy needs nothing for it: a `<script>` with a JSON type is a data block. The JSON
 * is encoded with `<`, `>`, `&` and both quotes escaped, so no markup inside it can end
 * the element.
 *
 * @internal Experimental until a consumer outside the academic extensions exists; it
 *           may change without a breaking change entry.
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
        $this->registerArgument('endpoint', 'bool', 'Name the icon endpoint of the current site and the version token', false, false);
    }

    public function render(): string
    {
        $sizeName = (string)($this->arguments['size'] ?? 'small');
        $size = FrontendIconRenderer::sizeFrom($sizeName);
        if ($size === null) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not an icon size; use default, small, medium, large or mega.', $sizeName),
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
        $endpoint = (bool)($this->arguments['endpoint'] ?? false) ? $this->endpointUrl() : null;
        if ($endpoint !== null) {
            $attributes['data-academic-icons-url'] = $endpoint;
            $attributes['data-academic-icons-version'] = $this->frontendIconRenderer->getVersion();
        }

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

    /**
     * The endpoint below the base of the site language the page is rendered in, which
     * is where a relative base such as `/de/` or a subfolder installation puts it.
     */
    private function endpointUrl(): ?string
    {
        // Declared nullable by Fluid 5 (TYPO3 v14) and not by Fluid 4 (TYPO3 v13), so
        // an instanceof check rather than a null check that one of them calls redundant.
        $renderingContext = $this->renderingContext;
        if (!$renderingContext instanceof RenderingContextInterface
            || !$renderingContext->hasAttribute(ServerRequestInterface::class)
        ) {
            return null;
        }
        $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        // A page outside every site is rendered with a NullSite, and the endpoint
        // answers below a real site only.
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return null;
        }
        $language = $request->getAttribute('language');
        $base = $language instanceof SiteLanguage ? (string)$language->getBase() : (string)$site->getBase();

        return rtrim($base, '/') . '/' . FrontendIconRenderer::ENDPOINT_PATH;
    }
}
