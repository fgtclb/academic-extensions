<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;
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
     * The group title of `Configuration/CategoryTypes.yaml` heads the types of the group in
     * the type select of a category, instead of the key `projects`.
     */
    #[Test]
    public function groupTitleHeadsTheTypesInTheTypeSelect(): void
    {
        $this->assertSame(
            'LLL:EXT:academic_projects/Resources/Private/Language/locallang.xlf:sys_category.projects.group',
            $GLOBALS['TCA']['sys_category']['columns']['type']['config']['itemGroups']['projects'] ?? null,
        );
    }

    /**
     * The declared group icon exists and is registered for inlining.
     */
    #[Test]
    public function groupIconIsShippedAndRegistered(): void
    {
        $group = $this->get(CategoryTypeRegistry::class)->getGroup('projects');
        $this->assertNotNull($group);
        $this->assertFileExists(GeneralUtility::getFileAbsFileName($group->getIcon()));

        $iconRegistry = $this->get(IconRegistry::class);
        $this->assertSame(
            CurrentColorSvgIconProvider::class,
            $iconRegistry->getIconConfigurationByIdentifier('category_types.group.projects')['provider'] ?? null,
        );
    }
}
