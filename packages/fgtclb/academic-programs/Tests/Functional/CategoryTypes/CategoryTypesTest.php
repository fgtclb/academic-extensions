<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CategoryTypesTest extends AbstractAcademicProgramsTestCase
{
    #[Test]
    public function extensionCategoryTypesYamlIsLoaded(): void
    {
        /** @var CategoryTypeRegistry $categoryTypeRegistry */
        $categoryTypeRegistry = $this->get(CategoryTypeRegistry::class);
        $groupedCategoryTypes = $categoryTypeRegistry->getGroupedCategoryTypes();
        $this->assertCount(1, array_keys($groupedCategoryTypes));
        $this->assertArrayHasKey('programs', $groupedCategoryTypes);
        $expected = include __DIR__ . '/Fixtures/DefaultExtensionCategoryTypes.php';
        $this->assertSame($expected, $categoryTypeRegistry->toArray());
    }

    /**
     * The registry above reads `types` only, so an icon of a type pointing at a missing
     * file fails where the icon is rendered, and the icon of a group is not read at all
     * (ACE-364). It named a file that never existed for years; every declared icon has to
     * be there, whether or not anything registers it yet.
     */
    #[Test]
    public function everyIconDeclaredInCategoryTypesYamlExists(): void
    {
        $configuration = Yaml::parseFile(__DIR__ . '/../../../Configuration/CategoryTypes.yaml');
        $entries = [...($configuration['groups'] ?? []), ...($configuration['types'] ?? [])];

        $this->assertCount(13, $entries);
        foreach ($entries as $entry) {
            $icon = (string)($entry['icon'] ?? '');
            $this->assertNotSame('', $icon, sprintf('"%s" declares no icon.', $entry['identifier'] ?? ''));
            $this->assertFileExists(
                GeneralUtility::getFileAbsFileName($icon),
                sprintf('The icon of "%s" does not exist.', $entry['identifier'] ?? ''),
            );
        }
    }
}
