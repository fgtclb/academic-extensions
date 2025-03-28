<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\CategoryTypes;

use PHPUnit\Framework\Attributes\Test;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CategoryTypesTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/category-types',
        'fgtclb/academic-projects',
    ];

    #[Test]
    public function extensionCategoryTypesYamlIsLoaded(): void
    {
        /** @var CategoryTypeRegistry $categoryTypeRegistry */
        $categoryTypeRegistry = $this->get(CategoryTypeRegistry::class);
        $groupedCategoryTypes = $categoryTypeRegistry->getGroupedCategoryTypes();
        $this->assertCount(1, array_keys($groupedCategoryTypes));
        $this->assertArrayHasKey('projects', $groupedCategoryTypes);
        $expected = include __DIR__ . '/Fixtures/DefaultExtensionCategoryTypes.php';
        $this->assertSame($expected, $categoryTypeRegistry->toArray());
    }
}
