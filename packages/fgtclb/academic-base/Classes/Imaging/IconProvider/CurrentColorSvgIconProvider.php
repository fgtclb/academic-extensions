<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Imaging\IconProvider;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Inlines an SVG file as the icon markup - in the default markup as well as in the
 * `inline` alternative - so that an icon drawn in `currentColor` takes the colour of
 * the text around it. Core's `SvgIconProvider` renders the default markup as `<img>`,
 * which is opaque to CSS: such an icon keeps the colours of its file, whatever the
 * backend colour scheme or the frontend theme says.
 *
 * Opt in per icon from `Configuration/Icons.php`:
 *
 *     'my-icon' => [
 *         'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
 *         'source' => 'EXT:my_extension/Resources/Public/Icons/my-icon.svg',
 *     ],
 *
 * The file has to be drawn for inlining: a `viewBox`, `fill="currentColor"` or
 * `stroke="currentColor"` on the shapes, no hardcoded colours and no `id` attributes -
 * the markup may appear several times in one document, and both an `id` and a `<style>`
 * rule are document global once inlined.
 *
 * The content is sanitised on both core versions before it goes into the page, so a
 * `<script>` element, an event handler attribute and a `javascript:` href are removed
 * rather than rendered - see `getSanitizedInlineSvg()` for why that takes a version
 * switch. It is a filter and not a warranty: the sources are meant to be files an
 * extension ships and registers itself, never uploads, and no sanitiser prevents an
 * inlined `id` or `<style>` from reaching past the icon into the rest of the document.
 *
 * A source this provider cannot inline yields no markup, on both core versions and for
 * every reason: the file is missing, unreadable, empty, not XML, or XML whose root is
 * not an `<svg>`. An icon is decoration, and a broken one must not be able to fail the
 * request that renders it.
 *
 * `AbstractSvgIconProvider` is `@internal` on both cores and TYPO3 v14 already rewrote its
 * internals once; re-read it on every core bump. Core's own `SvgIconProvider` extends it
 * the same way.
 *
 * No constructor on purpose. On TYPO3 v14 the parent class receives its collaborators
 * through `inject*()` setters, which the container wires for an autowired service only,
 * and it tags every `IconProviderInterface` as `icon.provider` and publishes it, so
 * `IconFactory` fetches the provider from the container. The class is therefore a
 * regular autowired service of `EXT:academic_base` and must not be excluded from the
 * container. TYPO3 v13 instantiates it with `new` and needs nothing.
 */
final class CurrentColorSvgIconProvider extends AbstractSvgIconProvider
{
    /**
     * @param array<string, mixed> $options
     */
    protected function generateMarkup(Icon $icon, array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException(
                '[' . $icon->getIdentifier() . '] The option "source" is required and must not be empty',
                1788480163,
            );
        }
        return $this->generateInlineMarkup($options);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function generateInlineMarkup(array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException(
                'The option "source" is required and must not be empty',
                1788480164,
            );
        }
        $source = (string)$options['source'];
        // TYPO3 v14 resolves an `EXT:` path itself through `SystemResourceFactory` and runs the
        // full `enshrined/svg-sanitize` pass over the content, so it is handed the path
        // unchanged and needs nothing else. TYPO3 v13 reads the file straight from disk, needs
        // an absolute path - not through the `_assets` symlink, so this also works outside
        // composer mode - and sanitises next to nothing, which is what the branch below is for.
        // @todo Remove the switch once TYPO3 v13 support is dropped.
        if ((new Typo3Version())->getMajorVersion() >= 14) {
            // TYPO3 v14 has the same uncaught sanitiser exception on its own inline path:
            // `getInlineSvg()` catches `InvalidSvgException` only, while
            // `SvgDocumentFactory::fromStringAndSanitize()` runs the sanitiser that throws
            // `\LogicException` - see `getSanitizedInlineSvg()` for the case. Caught here so
            // that an icon degrades to no markup on both core versions rather than turning a
            // record list or a page tree into a server error. Core's own `SvgIconProvider`
            // keeps the hole; fixing that belongs upstream.
            try {
                return $this->getInlineSvg($source);
            } catch (\LogicException) {
                return '';
            }
        }
        if (PathUtility::isExtensionPath($source) || !PathUtility::isAbsolutePath($source)) {
            $source = GeneralUtility::getFileAbsFileName($source);
        }
        return $this->getSanitizedInlineSvg($source);
    }

    /**
     * The TYPO3 v13 inline pipeline, with the sanitisation TYPO3 v14 already does.
     *
     * `AbstractSvgIconProvider::getInlineSvg()` removes `<script>` elements with a regular
     * expression on TYPO3 v13 and nothing else: an `onload` or `onclick` attribute, a
     * `javascript:` href and a `<foreignObject>` all pass through unchanged. That was
     * harmless while the default markup was an `<img>`, which executes nothing; this
     * provider inlines the file into the document, so the same content becomes a script
     * sink in the backend. Every SVG therefore goes through
     * `SvgSanitizer::sanitizeContent()` here, which is the identical
     * `enshrined/svg-sanitize` pass TYPO3 v14 applies through `SvgDocumentFactory` - the
     * class exists with this signature on TYPO3 v13.4 and v14.3, backed by the same
     * library version in both vendor trees.
     *
     * The sanitiser answers with a whole XML document, declaration included. Re-serialising
     * only the document element drops the declaration, which is the same thing the parent's
     * `simplexml` round trip does on TYPO3 v13.
     *
     * The price is that a comment does not survive on TYPO3 v13 either any more: the
     * sanitiser removes every node that is neither an element nor text. That aligns the two
     * cores rather than splitting them, and a licence attribution the icon set requires has
     * to be given outside the file in both cases.
     *
     * @todo Remove together with the version switch in `generateInlineMarkup()`.
     */
    private function getSanitizedInlineSvg(string $source): string
    {
        if (!file_exists($source)) {
            return '';
        }
        $svgContent = file_get_contents($source);
        if ($svgContent === false || $svgContent === '') {
            return '';
        }
        try {
            $svgContent = GeneralUtility::makeInstance(SvgSanitizer::class)->sanitizeContent($svgContent);
        } catch (\LogicException) {
            // `enshrined/svg-sanitize` throws `\LogicException` 1570870568 out of
            // `XPath::handleDefaultNamespace()` when the document does not carry exactly one
            // `<svg>` root - a file that is well-formed XML with any other root element, a
            // `<symbol>` fragment or an `<html>` document saved under an `.svg` name. Neither
            // core catches it, so it would leave the icon rendering and take the whole
            // response with it. Every other unusable source degrades to no markup here - a
            // file that is missing, unreadable, empty or not XML at all - and this one has to
            // degrade the same way.
            return '';
        }
        if ($svgContent === '') {
            return '';
        }
        $useInternalErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $loaded = $document->loadXML($svgContent);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);
        if (!$loaded || $document->documentElement === null) {
            return '';
        }
        return (string)$document->saveXML($document->documentElement);
    }
}
