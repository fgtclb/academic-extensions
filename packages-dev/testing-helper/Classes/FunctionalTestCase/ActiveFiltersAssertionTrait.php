<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

/**
 * Reads the active filter tags, the reset link and the result count of a partner, project or
 * program list out of a rendered page.
 *
 * Every helper takes the class prefix of the extension, `academic-<extension>`: the tags are
 * the links of `<prefix>-active-filters__tags`, the reset link is
 * `<prefix>-active-filters__reset` and the count is `<prefix>-result-count`. A page renders
 * each of them at most once, which the helpers assert.
 */
trait ActiveFiltersAssertionTrait
{
    /**
     * The tags in document order, keyed by their visible title, with the link and the
     * accessible label of each.
     *
     * @return array<string, array{href: string, label: string}>
     */
    private function activeFilterTags(string $html, string $prefix): array
    {
        $tags = [];
        foreach ($this->activeFiltersQuery($html, sprintf('//ul[contains(concat(" ", normalize-space(@class), " "), " %s-active-filters__tags ")]/li/a', $prefix)) as $link) {
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
    private function activeFiltersResetLink(string $html, string $prefix): ?string
    {
        $link = $this->activeFiltersSingle($html, sprintf('//a[contains(concat(" ", normalize-space(@class), " "), " %s-active-filters__reset ")]', $prefix));

        return $link?->getAttribute('href');
    }

    /**
     * The text of the result count, `null` when the page renders none.
     */
    private function activeFiltersResultCount(string $html, string $prefix): ?string
    {
        $paragraph = $this->activeFiltersSingle($html, sprintf('//p[contains(concat(" ", normalize-space(@class), " "), " %s-result-count ")]', $prefix));

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
