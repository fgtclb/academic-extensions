<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CoreVersionTest extends UnitTestCase
{
    #[Group(name: 'not-core-13')]
    #[Test]
    public function verifyCoreVersionTwelve(): void
    {
        $this->assertSame(12, (new Typo3Version())->getMajorVersion());
    }

    #[Group(name: 'not-core-12')]
    #[Test]
    public function verifyCoreVersionThirteen(): void
    {
        $this->assertSame(13, (new Typo3Version())->getMajorVersion());
    }
}
