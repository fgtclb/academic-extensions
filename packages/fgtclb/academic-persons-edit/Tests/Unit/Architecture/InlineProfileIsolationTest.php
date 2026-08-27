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
            self::EXTENSION_ROOT . '/Resources/Private/Templates/InlineProfile/List.html',
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
    public function inlineListUsesOnlyThePublicAcademicPersonsDetailController(): void
    {
        $template = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Templates/InlineProfile/List.html',
        );
        $this->assertIsString($template);
        $this->assertStringContainsString('action="detail"', $template);
        $this->assertStringContainsString('controller="Profile"', $template);
        $this->assertStringContainsString('pluginName="Detail"', $template);
        $this->assertStringContainsString('extensionName="academicpersons"', $template);
        $this->assertStringNotContainsString('action="show"', $template);
        $this->assertStringNotContainsString('action="edit"', $template);
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

    #[Test]
    public function inlineServicesUseTheSeparatedEditorSettingsService(): void
    {
        $services = file_get_contents(self::EXTENSION_ROOT . '/Configuration/Services.yaml');
        $factory = file_get_contents(
            self::EXTENSION_ROOT . '/Classes/Settings/AcademicPersonsEditSettingsFactory.php',
        );
        $this->assertIsString($services);
        $this->assertIsString($factory);
        $this->assertStringContainsString(
            '$academicPersonsSettings: \'@academic_persons_edit.settings\'',
            $services,
        );
        $this->assertStringContainsString('academic_persons_edit.settings:', $services);
        $this->assertStringContainsString(
            'Configuration/AcademicsPersonsEdit/Settings.yaml',
            $factory,
        );
        $this->assertStringNotContainsString('Configuration/AcademicPersons/Settings.yaml', $factory);
    }

    #[Test]
    public function editorSettingsAreNeverAppliedToBackendTca(): void
    {
        $compatibilityMarkerPath = self::EXTENSION_ROOT . '/Classes/Tca/SettingsValidationOverrides.php';
        if (is_file($compatibilityMarkerPath)) {
            $compatibilityMarker = file_get_contents($compatibilityMarkerPath);
            $this->assertIsString($compatibilityMarker);
            $this->assertStringNotContainsString('AcademicPersonsEditSettingsFactory', $compatibilityMarker);
            $this->assertStringNotContainsString('$GLOBALS[\'TCA\']', $compatibilityMarker);
            $this->assertStringNotContainsString('function apply', $compatibilityMarker);
        }
        $overrideFiles = glob(self::EXTENSION_ROOT . '/Configuration/TCA/Overrides/*.php');
        $this->assertIsArray($overrideFiles);
        foreach ($overrideFiles as $overrideFile) {
            $source = file_get_contents($overrideFile);
            $this->assertIsString($source);
            $this->assertStringNotContainsString('AcademicPersonsEditSettingsFactory', $source);
            $this->assertStringNotContainsString('SettingsValidationOverrides', $source);
        }
    }
}
