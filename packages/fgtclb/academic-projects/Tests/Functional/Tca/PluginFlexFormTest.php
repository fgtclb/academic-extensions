<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Tca;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\PluginFlexFormDataStructureTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Guards the FlexForm data structure of the plugins against a shape that only
 * works on one of the supported core versions.
 *
 * @see PluginFlexFormDataStructureTrait
 */
final class PluginFlexFormTest extends AbstractAcademicProjectsTestCase
{
    use PluginFlexFormDataStructureTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function pluginContentTypeDataProvider(): \Generator
    {
        yield 'Project list' => ['academicprojects_projectlist'];
        yield 'Single project list' => ['academicprojects_projectlistsingle'];
    }

    #[Test]
    #[DataProvider('pluginContentTypeDataProvider')]
    public function pluginFlexFormIsResolvedForContentType(string $cType): void
    {
        $this->assertPluginFlexFormIsResolved($cType);
    }

    /**
     * The state badge of the project cards is an option of both content elements, off
     * unless an editor switches it on.
     */
    #[Test]
    #[DataProvider('pluginContentTypeDataProvider')]
    public function theStateBadgeOptionIsOffByDefault(string $cType): void
    {
        $field = $this->resolvePluginFlexFormDataStructure($cType)['sheets']['sDEF']['ROOT']['el']['settings.showActiveStateBadge'] ?? null;

        $this->assertIsArray($field);
        $this->assertSame('check', $field['config']['type'] ?? null);
        $this->assertSame('checkboxToggle', $field['config']['renderType'] ?? null);
        $this->assertSame('0', (string)($field['config']['default'] ?? ''));
    }
}
