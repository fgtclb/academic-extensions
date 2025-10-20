<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Settings\AcademicSettingsFactory;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\TestAcademicSettings\Settings\SimpleSettings;
use PHPUnit\Framework\Attributes\Test;

final class SimpleSettingsTest extends AbstractAcademicBaseTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-academic-settings';
        parent::setUp();
    }

    #[Test]
    public function getInstanceFromContainerReturnsSettingsFromTestFixtureExtensionFile(): void
    {
        $expectedSomeArray = [
            'key1' => 'value1',
            'key2' => true,
        ];
        $simpleSettings = $this->get(SimpleSettings::class);
        $this->assertInstanceOf(SimpleSettings::class, $simpleSettings);
        $this->assertTrue($simpleSettings->someFlag);
        $this->assertSame($expectedSomeArray, $simpleSettings->someArray);
    }
}
