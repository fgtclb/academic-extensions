<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class InlineProfileIsolationTest extends TestCase
{
    private const EXTENSION_ROOT = __DIR__ . '/../../..';

    #[Test]
    public function inlineProductionSourcesDoNotReferenceLegacyPluginResources(): void
    {
        $sourceFiles = [
            self::EXTENSION_ROOT . '/Classes/Controller/InlineProfileController.php',
            self::EXTENSION_ROOT . '/Resources/Private/Templates/InlineProfile/Index.html',
            self::EXTENSION_ROOT . '/Resources/Public/JavaScript/frontend/profile.js',
        ];
        $partials = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile',
                FilesystemIterator::SKIP_DOTS,
            ),
        );
        foreach ($partials as $partial) {
            if ($partial->isFile() && $partial->getExtension() === 'html') {
                $sourceFiles[] = $partial->getPathname();
            }
        }
        $javaScriptModules = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                self::EXTENSION_ROOT . '/Resources/Public/JavaScript/frontend/profile',
                FilesystemIterator::SKIP_DOTS,
            ),
        );
        foreach ($javaScriptModules as $javaScriptModule) {
            if ($javaScriptModule->isFile() && $javaScriptModule->getExtension() === 'js') {
                $sourceFiles[] = $javaScriptModule->getPathname();
            }
        }
        $forbiddenReferences = [
            'academicpersonsedit_profileediting',
            'AbstractProfileEditingPluginTestCase',
            'ProfileController::class',
            'ProfileInformationController::class',
            'ContractController::class',
            'partial="Profile/',
            'partial="ProfileInformation/',
            'partial="Contract/',
            'controller="Profile"',
            'controller="ProfileInformation"',
            'controller="Contract"',
        ];
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            $this->assertIsString($source);
            foreach ($forbiddenReferences as $forbiddenReference) {
                $this->assertStringNotContainsString(
                    $forbiddenReference,
                    $source,
                    sprintf('Inline source "%s" depends on legacy ProfileEditing code.', $sourceFile),
                );
            }
        }
    }

    #[Test]
    public function inlineFunctionalFixtureStartsWithInlineContentType(): void
    {
        $fixture = file_get_contents(
            self::EXTENSION_ROOT
                . '/Tests/Functional/Plugins/Fixtures/AcademicPersonsEditInlineProfile/inlineProfilePage.csv',
        );
        $this->assertIsString($fixture);
        $this->assertStringContainsString('academicpersonsedit_inlineprofile', $fixture);
        $this->assertStringNotContainsString('academicpersonsedit_profileediting', $fixture);
    }
}
