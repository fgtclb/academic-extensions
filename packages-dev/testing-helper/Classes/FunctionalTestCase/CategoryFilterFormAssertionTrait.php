<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

/**
 * Reads the category filters out of the filter form of a partner, project or program list.
 *
 * A category filter is a select named `…[demand][filterCollection][<category type>]`; the
 * sorting selects of the same form are not filters. The type is read from the name, not
 * from the `id`, which a form may make unique per content element. Filters
 * after the first few can sit in a `details` element, the "More filters" disclosure, and a
 * test has to tell the two places apart: a string assertion on the page passes for a filter
 * in either place, and for a disclosure that is open when it should be closed.
 *
 * Every helper takes the class of the form, `academic-<extension>-filtersorting`, so a page
 * with two lists is read one form at a time.
 */
trait CategoryFilterFormAssertionTrait
{
    /**
     * The category types of the filters, in document order, split by where they render:
     * `visible` outside the disclosure, `more` inside it. `disclosure` is `none` without a
     * `details` element, else `open` or `closed`, and `summary` its label.
     *
     * @return array{visible: list<string>, more: list<string>, disclosure: 'none'|'open'|'closed', summary: string|null}
     */
    private function renderedCategoryFilters(string $html, string $formClass): array
    {
        [$xpath, $form] = $this->categoryFilterForm($html, $formClass);

        $filters = ['visible' => [], 'more' => []];
        foreach ($this->categoryFilterQuery($xpath, './/select[contains(@name, "[demand][filterCollection]")]', $form) as $select) {
            $this->assertInstanceOf(\DOMElement::class, $select);
            $inDisclosure = $this->categoryFilterQuery($xpath, 'ancestor::details', $select)->length > 0;
            $filters[$inDisclosure ? 'more' : 'visible'][] = $this->categoryFilterType($select);
        }

        $disclosures = $this->categoryFilterQuery($xpath, './/details', $form);
        $this->assertLessThanOrEqual(1, $disclosures->length, 'The form renders more than one disclosure.');
        $disclosure = $disclosures->item(0);
        if (!$disclosure instanceof \DOMElement) {
            return [...$filters, 'disclosure' => 'none', 'summary' => null];
        }
        $summary = $this->categoryFilterQuery($xpath, './summary', $disclosure)->item(0);

        return [
            ...$filters,
            'disclosure' => $disclosure->hasAttribute('open') ? 'open' : 'closed',
            'summary' => $summary === null ? null : trim($summary->textContent),
        ];
    }

    /**
     * The options of the filter of one category type, in document order, as their label -
     * with ` (disabled)` appended to a disabled one.
     *
     * @return list<string>
     */
    private function categoryFilterOptions(string $html, string $formClass, string $typeIdentifier): array
    {
        [$xpath, $form] = $this->categoryFilterForm($html, $formClass);
        $selects = [];
        foreach ($this->categoryFilterQuery($xpath, './/select[contains(@name, "[demand][filterCollection]")]', $form) as $select) {
            $this->assertInstanceOf(\DOMElement::class, $select);
            if ($this->categoryFilterType($select) === $typeIdentifier) {
                $selects[] = $select;
            }
        }
        $this->assertCount(1, $selects, sprintf('The form renders %d filters of the type "%s".', count($selects), $typeIdentifier));

        $options = [];
        foreach ($this->categoryFilterQuery($xpath, './/option', $selects[0]) as $option) {
            $this->assertInstanceOf(\DOMElement::class, $option);
            $options[] = trim($option->textContent) . ($option->hasAttribute('disabled') ? ' (disabled)' : '');
        }

        return $options;
    }

    /**
     * The markup of the cell around each category filter, with the whitespace between and
     * inside tags collapsed: what a regression pin compares, without depending on the
     * indentation of a template.
     *
     * @return list<string>
     */
    private function categoryFilterCellMarkup(string $html, string $formClass): array
    {
        [$xpath, $form] = $this->categoryFilterForm($html, $formClass);

        $cells = [];
        foreach ($this->categoryFilterQuery($xpath, './/select[contains(@name, "[demand][filterCollection]")]/..', $form) as $cell) {
            $markup = (string)$cell->ownerDocument?->saveHTML($cell);
            $cells[] = trim((string)preg_replace(['/>\s+</', '/\s+/'], ['><', ' '], $markup));
        }

        return $cells;
    }

    private function categoryFilterType(\DOMElement $select): string
    {
        $this->assertMatchesRegularExpression('/\[demand\]\[filterCollection\]\[[^\]]+\]$/', $select->getAttribute('name'));

        return (string)preg_replace('/^.*\[([^\]]+)\]$/', '$1', $select->getAttribute('name'));
    }

    /**
     * @return array{0: \DOMXPath, 1: \DOMElement}
     */
    private function categoryFilterForm(string $html, string $formClass): array
    {
        $document = new \DOMDocument();
        // The prefix makes libxml read a page without a charset declaration as UTF-8
        // rather than ISO-8859-1.
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);
        $xpath = new \DOMXPath($document);
        $forms = $this->categoryFilterQuery(
            $xpath,
            sprintf('//form[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $formClass),
        );
        $this->assertSame(1, $forms->length, sprintf('The page renders %d forms of the class "%s".', $forms->length, $formClass));
        $form = $forms->item(0);
        $this->assertInstanceOf(\DOMElement::class, $form);

        return [$xpath, $form];
    }

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    private function categoryFilterQuery(\DOMXPath $xpath, string $query, ?\DOMNode $contextNode = null): \DOMNodeList
    {
        $nodes = $xpath->query($query, $contextNode);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes, sprintf('The query "%s" is invalid.', $query));

        return $nodes;
    }
}
