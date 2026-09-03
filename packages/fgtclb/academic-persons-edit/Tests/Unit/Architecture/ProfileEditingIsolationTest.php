<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProfileEditingIsolationTest extends TestCase
{
    private const EXTENSION_ROOT = __DIR__ . '/../../..';

    #[Test]
    public function profileEditingSourcesDoNotReferenceRemovedControllersOrViews(): void
    {
        $sourceFiles = [
            self::EXTENSION_ROOT . '/Classes/Controller/ProfileController.php',
            self::EXTENSION_ROOT . '/Resources/Private/Templates/Profile/Index.html',
            self::EXTENSION_ROOT . '/Resources/Private/Templates/Profile/List.html',
            self::EXTENSION_ROOT . '/Resources/Public/JavaScript/frontend/profile.js',
        ];
        $partials = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                self::EXTENSION_ROOT . '/Resources/Private/Partials/Profile',
                \FilesystemIterator::SKIP_DOTS,
            ),
        );
        foreach ($partials as $partial) {
            if ($partial->isFile() && $partial->getExtension() === 'html') {
                $sourceFiles[] = $partial->getPathname();
            }
        }
        $javaScriptModules = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                self::EXTENSION_ROOT . '/Resources/Public/JavaScript/frontend/profile',
                \FilesystemIterator::SKIP_DOTS,
            ),
        );
        foreach ($javaScriptModules as $javaScriptModule) {
            if ($javaScriptModule->isFile() && $javaScriptModule->getExtension() === 'js') {
                $sourceFiles[] = $javaScriptModule->getPathname();
            }
        }
        $forbiddenReferences = [
            'AbstractProfileEditingPluginTestCase',
            'ProfileInformationController::class',
            'ContractController::class',
            'PhysicalAddressController::class',
            'EmailAddressController::class',
            'PhoneNumberController::class',
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
                    sprintf('Profile editing source "%s" depends on removed controller code.', $sourceFile),
                );
            }
        }
    }

    #[Test]
    public function profileListUsesThePublicAcademicPersonsDetailPlugin(): void
    {
        $template = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Templates/Profile/List.html',
        );
        $this->assertIsString($template);
        $this->assertStringContainsString('action="detail"', $template);
        $this->assertStringContainsString('controller="Profile"', $template);
        $this->assertStringContainsString('pluginName="Detail"', $template);
        $this->assertStringContainsString('pageUid="{settings.detailPid}"', $template);
        $this->assertStringContainsString('extensionName="academicpersons"', $template);
        $this->assertStringNotContainsString('pluginName="ListAndDetail"', $template);
        $this->assertStringNotContainsString('action="show"', $template);
        $this->assertStringNotContainsString('action="edit"', $template);
    }

    #[Test]
    public function functionalFixtureUsesTheStableProfileEditingContentType(): void
    {
        $fixture = file_get_contents(
            self::EXTENSION_ROOT
                . '/Tests/Functional/Plugins/Fixtures/AcademicPersonsEditProfileEditing/profileEditingPage.csv',
        );
        $this->assertIsString($fixture);
        $this->assertStringContainsString('academicpersonsedit_profileediting', $fixture);
        $this->assertStringNotContainsString('academicpersonsedit_inlineprofile', $fixture);
    }

    #[Test]
    public function profileEditingServicesUseTheCentralAcademicPersonsSettingsService(): void
    {
        $services = file_get_contents(self::EXTENSION_ROOT . '/Configuration/Services.yaml');
        $personsServices = file_get_contents(
            self::EXTENSION_ROOT . '/../academic-persons/Configuration/Services.yaml',
        );
        $personsSettingsFactory = file_get_contents(
            self::EXTENSION_ROOT . '/../academic-persons/Classes/Settings/AcademicPersonsSettingsFactory.php',
        );
        $this->assertIsString($services);
        $this->assertIsString($personsServices);
        $this->assertIsString($personsSettingsFactory);
        $this->assertStringContainsString(
            '$academicPersonsSettings: \'@FGTCLB\\AcademicPersons\\Settings\\AcademicPersonsSettings\'',
            $services,
        );
        $this->assertStringNotContainsString('academic_persons_edit.settings:', $services);
        $this->assertStringContainsString(
            'Configuration/AcademicPersons/Settings.yaml',
            $personsSettingsFactory,
        );
        $this->assertFileExists(
            self::EXTENSION_ROOT . '/../academic-persons/Configuration/AcademicPersons/Settings.yaml',
        );
        $this->assertFileDoesNotExist(
            self::EXTENSION_ROOT . '/Configuration/AcademicsPersonsEdit/Settings.yaml',
        );
    }

    #[Test]
    public function centralSettingsAreAppliedToBackendTca(): void
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
        $tcaFiles = glob(self::EXTENSION_ROOT . '/../academic-persons/Configuration/TCA/*.php');
        $this->assertIsArray($tcaFiles);
        $tcaSource = implode('', array_map(static fn(string $file): string => (string)file_get_contents($file), $tcaFiles));
        $this->assertStringContainsString('getProfileUpdateValidationTcaTableConfig', $tcaSource);
        $this->assertStringContainsString('getContractContactValidationTcaTableConfig', $tcaSource);
        $this->assertStringContainsString('getDocumentValidationTca', $tcaSource);
    }

    #[Test]
    public function profileEditingContractFormUsesTheNormalizedContractFieldStructure(): void
    {
        $controller = file_get_contents(self::EXTENSION_ROOT . '/Classes/Controller/ProfileController.php');
        $settingsFactory = file_get_contents(
            self::EXTENSION_ROOT . '/../academic-persons/Classes/Settings/AcademicPersonsSettingsFactory.php',
        );
        $this->assertIsString($controller);
        $this->assertIsString($settingsFactory);
        $this->assertStringContainsString(
            'foreach ($this->academicPersonsSettings->contractFields as $field)',
            $controller,
        );
        $this->assertStringContainsString("['contracts']['contactSections']", $controller);
        $this->assertStringContainsString('($contracts[\'fields\'] ?? null)', $settingsFactory);
        $this->assertStringContainsString('($contracts[\'contactSections\'] ?? null)', $settingsFactory);
    }
}
