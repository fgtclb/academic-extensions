<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CategoryTypesTest extends AbstractAcademicProjectsTestCase
{
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

    /**
     * An icon path in the YAML is only a string: EXT:category_types registers whatever it
     * names, and a missing file renders as an empty icon rather than failing. The group
     * entry is included although EXT:category_types does not read group icons yet
     * (ACE-364) - it named a file that never existed, and it must not do so again.
     */
    #[Test]
    public function everyIconDeclaredInCategoryTypesYamlExists(): void
    {
        $configuration = Yaml::parseFile(dirname(__DIR__, 3) . '/Configuration/CategoryTypes.yaml');
        $entries = [...($configuration['groups'] ?? []), ...($configuration['types'] ?? [])];
        $this->assertNotSame([], $entries);

        foreach ($entries as $entry) {
            $icon = (string)($entry['icon'] ?? '');
            $this->assertNotSame('', $icon, sprintf('"%s" declares no icon.', (string)($entry['identifier'] ?? '')));
            $this->assertFileExists(
                GeneralUtility::getFileAbsFileName($icon),
                sprintf('"%s" names the icon "%s", which does not exist.', (string)($entry['identifier'] ?? ''), $icon),
            );
        }
    }
}
