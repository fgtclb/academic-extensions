<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Programs and projects both shipped a type `department` (ACE-64). A category stores the
 * identifier without its group, so the type select offered two items with the value
 * `department`, and a saved category reopened with whichever of the two came first. With
 * the projects type renamed, every item has a value of its own.
 */
final class CategoryTypeSelectWithProgramsTest extends AbstractAcademicProjectsTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'fgtclb/academic-programs';
        parent::setUp();
    }

    #[Test]
    public function everyTypeItemHasAValueOfItsOwn(): void
    {
        $values = array_column($GLOBALS['TCA']['sys_category']['columns']['type']['config']['items'], 'value');

        $this->assertSame(
            [],
            array_keys(array_filter(array_count_values($values), static fn(int $count): bool => $count > 1)),
        );
    }

    #[Test]
    public function eachDepartmentIsOfferedInItsOwnGroup(): void
    {
        $groups = [];
        foreach ($GLOBALS['TCA']['sys_category']['columns']['type']['config']['items'] as $item) {
            $groups[$item['value']] = $item['group'] ?? null;
        }

        $this->assertSame('programs', $groups['department'] ?? null);
        $this->assertSame('projects', $groups['project_department'] ?? null);
    }

    #[Test]
    public function eachDepartmentHasTheIconOfItsExtension(): void
    {
        $typeIcons = $GLOBALS['TCA']['sys_category']['ctrl']['typeicon_classes'];

        $this->assertSame('category_types.programs.department', $typeIcons['department'] ?? null);
        $this->assertSame('category_types.projects.project_department', $typeIcons['project_department'] ?? null);
    }
}
