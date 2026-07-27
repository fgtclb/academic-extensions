<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Tca;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\PluginFlexFormDataStructureTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Guards the FlexForm data structure of the plugins against a shape that only
 * works on one of the supported core versions.
 *
 * @see PluginFlexFormDataStructureTrait
 */
final class PluginFlexFormTest extends AbstractAcademicBiteJobsTestCase
{
    use PluginFlexFormDataStructureTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function pluginContentTypeDataProvider(): \Generator
    {
        yield 'Job list' => ['academicbitejobs_list'];
    }

    #[Test]
    #[DataProvider('pluginContentTypeDataProvider')]
    public function pluginFlexFormIsResolvedForContentType(string $cType): void
    {
        $this->assertPluginFlexFormIsResolved($cType);
    }
}
