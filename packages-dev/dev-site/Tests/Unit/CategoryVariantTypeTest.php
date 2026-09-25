<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Every language variant of a category in `Scenario.yaml` declares the category type of its
 * original.
 *
 * `sys_category.type` is the record type column and not `l10n_mode: exclude`: a variant
 * that leaves it out is stored as `default`, and the language overlay of the category
 * repository of `category_types` then reads that type in the translated language. Every
 * German category was of the type `default` that way, so no German list offered a category
 * filter, the German program finder no form, and German program pages no category facts -
 * with nothing failing. Localising a category in the backend copies the type; a seed has to
 * state it.
 */
final class CategoryVariantTypeTest extends TestCase
{
    #[Test]
    public function everyCategoryVariantHasTheTypeOfItsOriginal(): void
    {
        $scenario = Yaml::parseFile(dirname(__DIR__, 2) . '/Configuration/DataFactory/academics-instance/Scenario.yaml');
        $this->assertIsArray($scenario);

        $variants = 0;
        $mismatches = [];
        foreach ($this->categories($scenario) as $category) {
            // A category without a type and a variant without one agree: both are "default".
            $type = $category['self']['type'] ?? null;
            foreach ($category['languageVariants'] ?? [] as $variant) {
                $variants++;
                if (($variant['self']['type'] ?? null) !== $type) {
                    $mismatches[] = sprintf(
                        'category %s ("%s"): variant %s has type %s',
                        (string)($category['self']['id'] ?? '?'),
                        (string)$type,
                        (string)($variant['self']['id'] ?? '?'),
                        var_export($variant['self']['type'] ?? null, true),
                    );
                }
            }
        }

        $this->assertGreaterThan(0, $variants, 'The seed declares no category variant at all.');
        $this->assertSame([], $mismatches);
    }

    /**
     * Every `category:` entity list below the page tree of the scenario.
     *
     * @param array<mixed> $node
     * @return \Generator<array<mixed>>
     */
    private function categories(array $node): \Generator
    {
        foreach ($node as $key => $child) {
            if (!is_array($child)) {
                continue;
            }
            if ($key === 'category' && array_is_list($child)) {
                foreach ($child as $category) {
                    if (is_array($category) && isset($category['self'])) {
                        yield $category;
                    }
                }
                continue;
            }
            yield from $this->categories($child);
        }
    }
}
