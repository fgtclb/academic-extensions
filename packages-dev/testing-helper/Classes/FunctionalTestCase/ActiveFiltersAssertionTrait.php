<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

/**
 * Reads the active filter tags, the reset link and the result count of a partner, project or
 * program list out of a rendered page.
 *
 * Every helper takes an XPath expression selecting the element it reads, which the test of
 * an extension knows: the element of the active filters, whose list holds the tags and whose
 * link is the reset link, or the element of the result count. A page renders each of them at
 * most once, which the helpers assert.
 */
trait ActiveFiltersAssertionTrait
{
    /**
     * The tags in document order, keyed by their visible title, with the link and the
     * accessible label of each.
     *
     * @return array<string, array{href: string, label: string}>
     */
    private function activeFilterTags(string $html, string $activeFilters): array
    {
        $tags = [];
        foreach ($this->activeFiltersQuery($html, $activeFilters . '/ul/li/a') as $link) {
            $this->assertInstanceOf(\DOMElement::class, $link);
            // The first child is the title, the second the hidden "×".
            $title = trim((string)$link->firstChild?->textContent);
            $this->assertArrayNotHasKey($title, $tags, 'Two tags are titled ' . $title);
            $tags[$title] = ['href' => $link->getAttribute('href'), 'label' => $link->getAttribute('aria-label')];
        }

        return $tags;
    }

    /**
     * The link target of the reset link, `null` when the page renders none.
     */
    private function activeFiltersResetLink(string $html, string $activeFilters): ?string
    {
        $link = $this->activeFiltersSingle($html, $activeFilters . '/a');

        return $link?->getAttribute('href');
    }

    /**
     * The text of the result count, `null` when the page renders none.
     */
    private function activeFiltersResultCount(string $html, string $resultCount): ?string
    {
        $paragraph = $this->activeFiltersSingle($html, $resultCount);

        return $paragraph === null ? null : trim($paragraph->textContent);
    }

    /**
     * The demand a link of a list carries, keys sorted.
     *
     * @return array<string, mixed>
     */
    private function activeFiltersDemand(string $url, string $pluginNamespace): array
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $demand = $query[$pluginNamespace]['demand'] ?? null;
        $this->assertIsArray($demand, 'The URL carries no demand: ' . $url);
        ksort($demand);

        return $demand;
    }

    private function activeFiltersSingle(string $html, string $expression): ?\DOMElement
    {
        $nodes = $this->activeFiltersQuery($html, $expression);
        if ($nodes->length === 0) {
            return null;
        }
        $this->assertSame(1, $nodes->length, 'The page renders it more than once: ' . $expression);
        $node = $nodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $node);

        return $node;
    }

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    private function activeFiltersQuery(string $html, string $expression): \DOMNodeList
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $nodes = (new \DOMXPath($document))->query($expression);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);

        return $nodes;
    }
}
