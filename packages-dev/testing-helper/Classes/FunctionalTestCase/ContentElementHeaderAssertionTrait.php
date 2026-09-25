<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

/**
 * Counts what the header of a content element rendered into a page.
 *
 * A plugin content element renders through `lib.contentElement`, whose layout renders the
 * header an editor enters. A plugin template that renders the header partial as well shows
 * it twice, and with the header layout "Default" leaves an empty `<header>` behind - both
 * pass an assertion that only looks for the text. These helpers count instead: the headings
 * carrying a text, and the `header` elements, each in the whole page or below the element a
 * scope selects, so a test can say where the header rendered and how often.
 */
trait ContentElementHeaderAssertionTrait
{
    /**
     * Counts the headings, `h1` to `h6`, whose text is the given one.
     *
     * @param string $scope an XPath expression selecting the element to count below, the
     *                      whole page when empty
     */
    private function countHeadingsReading(string $html, string $text, string $scope = ''): int
    {
        return $this->countContentElementHeaderNodes(
            $html,
            sprintf(
                '%s//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6][normalize-space() = %s]',
                $scope,
                $this->contentElementHeaderXPathLiteral($text),
            ),
        );
    }

    /**
     * Counts the `header` elements, whatever they contain.
     *
     * @param string $scope an XPath expression selecting the element to count below, the
     *                      whole page when empty
     */
    private function countHeaderElements(string $html, string $scope = ''): int
    {
        return $this->countContentElementHeaderNodes($html, $scope . '//header');
    }

    private function countContentElementHeaderNodes(string $html, string $query): int
    {
        $document = new \DOMDocument();
        // The prefix makes libxml read a page without a charset declaration as UTF-8
        // rather than ISO-8859-1.
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);
        $nodes = (new \DOMXPath($document))->query($query);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes, sprintf('The query "%s" is invalid.', $query));

        return $nodes->length;
    }

    /**
     * An XPath string literal for the text: XPath 1.0 has no escape, so a text holding
     * both quote characters is assembled with `concat()`.
     */
    private function contentElementHeaderXPathLiteral(string $text): string
    {
        if (!str_contains($text, '"')) {
            return '"' . $text . '"';
        }
        if (!str_contains($text, "'")) {
            return "'" . $text . "'";
        }

        return 'concat("' . str_replace('"', '", \'"\', "', $text) . '")';
    }
}
