<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsSync\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final class ExtensionLoadedTest extends AbstractAcademicPersonsSyncTestCase
{
    #[Test]
    public function testCaseLoadsExtension(): void
    {
        $this->assertContains('fgtclb/academic-persons-sync', $this->testExtensionsToLoad);
    }

    #[Test]
    public function extensionIsLoaded(): void
    {
        $this->assertTrue(ExtensionManagementUtility::isLoaded('academic_persons_sync'));
    }
}
