<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

/**
 * Asserts what the shared image partial of academic_base rendered into a page.
 *
 * The partial answers with one of four presets, and the preset is not visible in the
 * markup: every one of them renders a `<picture>` with WebP sources and a fallback
 * `<img>`. What separates them is how many sources they declare and how wide the
 * fallback is, so both are asserted - a template that asks for `card` where it should
 * ask for `logo` renders a picture either way and would pass a bare "is a picture"
 * assertion.
 *
 * The fallback width is the processed width, which is the preset's `maxWidth` capped by
 * the width of the source file: an 800 pixel wide fixture is not upscaled to the 1200 of
 * the `detail` preset. The tests therefore name the width they expect for their own
 * fixture rather than the preset's number.
 */
trait ResponsiveImageAssertionTrait
{
    private function parseRenderedPage(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML($html, LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    private function nodesMatching(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): \DOMNodeList
    {
        $nodes = $xpath->query($query, $context);
        self::assertInstanceOf(\DOMNodeList::class, $nodes, sprintf('The query "%s" is invalid.', $query));

        return $nodes;
    }

    private function countNodesMatching(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): int
    {
        return $this->nodesMatching($xpath, $query, $context)->length;
    }

    private function elementMatching(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): \DOMElement
    {
        $nodes = $this->nodesMatching($xpath, $query, $context);
        self::assertSame(1, $nodes->length, sprintf('The query "%s" does not match exactly one element.', $query));
        $element = $nodes->item(0);
        self::assertInstanceOf(\DOMElement::class, $element);

        return $element;
    }

    /**
     * @param string $class the class of the fallback image, which the partial takes as an argument
     * @param string|null $alt the alternative text the file carries, null to assert nothing about it.
     *                         The partial passes no `alt` of its own here, and the image view helper
     *                         falls back to the `alternative` of the file for an `alt` that is not
     *                         set - so an empty string would be a regression rather than a detail.
     */
    private function assertRendersResponsivePicture(
        \DOMXPath $xpath,
        \DOMNode $context,
        int $expectedSources,
        int $expectedFallbackWidth,
        string $class,
        ?string $alt = null,
    ): void {
        $picture = $this->elementMatching($xpath, './/picture', $context);
        $sources = $this->nodesMatching($xpath, './source', $picture);
        self::assertSame(
            $expectedSources,
            $sources->length,
            'The picture declares a different number of sources than the expected preset does.',
        );
        foreach ($sources as $source) {
            self::assertInstanceOf(\DOMElement::class, $source);
            self::assertSame('image/webp', $source->getAttribute('type'));
            self::assertStringEndsWith('.webp', $source->getAttribute('srcset'));
        }
        $image = $this->elementMatching($xpath, './img', $picture);
        self::assertSame((string)$expectedFallbackWidth, $image->getAttribute('width'));
        self::assertSame($class, $image->getAttribute('class'));
        self::assertSame('lazy', $image->getAttribute('loading'));
        if ($alt !== null) {
            self::assertSame($alt, $image->getAttribute('alt'));
        }
    }

    /**
     * A vector image is passed through unprocessed: no `<picture>`, no WebP source, and the
     * public URL of the original file.
     */
    private function assertRendersUnprocessedSvg(
        \DOMXPath $xpath,
        \DOMNode $context,
        string $fileName,
        string $class,
        ?string $alt = null,
    ): void {
        self::assertSame(0, $this->countNodesMatching($xpath, './/picture', $context));
        $image = $this->elementMatching($xpath, sprintf('.//img[@class="%s"]', $class), $context);
        self::assertStringEndsWith($fileName, $image->getAttribute('src'));
        self::assertStringNotContainsString('.webp', $image->getAttribute('src'));
        self::assertSame('lazy', $image->getAttribute('loading'));
        if ($alt !== null) {
            self::assertSame($alt, $image->getAttribute('alt'));
        }
    }

    /**
     * Neither an image nor a placeholder. The image is looked up by the class the template
     * passes to the partial, because an item may well carry icons, and an icon is an `<img>`
     * as much as a photo is.
     */
    private function assertRendersNoImage(\DOMXPath $xpath, \DOMNode $context, string $class): void
    {
        self::assertSame(0, $this->countNodesMatching($xpath, './/picture', $context));
        self::assertSame(0, $this->countNodesMatching($xpath, sprintf('.//img[@class="%s"]', $class), $context));
    }
}
