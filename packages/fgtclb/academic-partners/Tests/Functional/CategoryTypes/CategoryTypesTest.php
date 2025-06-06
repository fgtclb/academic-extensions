<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\Test;

final class CategoryTypesTest extends AbstractAcademicPartnersTestCase
{
    #[Test]
    public function extensionCategoryTypesYamlIsLoaded(): void
    {
        /** @var CategoryTypeRegistry $categoryTypeRegistry */
        $categoryTypeRegistry = $this->get(CategoryTypeRegistry::class);
        $groupedCategoryTypes = $categoryTypeRegistry->getGroupedCategoryTypes();
        $this->assertCount(1, array_keys($groupedCategoryTypes));
        $this->assertArrayHasKey('partners', $groupedCategoryTypes);
        $expected = include __DIR__ . '/Fixtures/DefaultExtensionCategoryTypes.php';
        $this->assertSame($expected, $categoryTypeRegistry->toArray());
    }
}
