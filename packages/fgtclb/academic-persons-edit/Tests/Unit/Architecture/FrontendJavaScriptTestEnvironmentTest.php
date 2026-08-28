<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Architecture;

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FrontendJavaScriptTestEnvironmentTest extends TestCase
{
    private const EXTENSION_ROOT = __DIR__ . '/../../..';

    #[Test]
    public function developmentPackageRunsJestWithOneNpmCommand(): void
    {
        $developmentDirectory = self::EXTENSION_ROOT . '/Resources/Public/Development';
        $packageFile = $developmentDirectory . '/package.json';
        $this->assertFileExists($packageFile);
        $packageSource = file_get_contents($packageFile);
        $this->assertIsString($packageSource);
        try {
            $package = json_decode($packageSource, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            self::fail('Invalid JavaScript development package: ' . $exception->getMessage());
        }
        $this->assertIsArray($package);
        $this->assertSame('module', $package['type'] ?? null);
        $this->assertStringContainsString(
            'verify-esm-environment.js',
            (string)($package['scripts']['test'] ?? ''),
        );
        $this->assertStringContainsString('jest', (string)($package['scripts']['test'] ?? ''));
        $this->assertStringNotContainsString(
            '--experimental-vm-modules',
            (string)($package['scripts']['test'] ?? ''),
        );
        $this->assertArrayHasKey('@babel/core', $package['devDependencies'] ?? []);
        $this->assertArrayHasKey('@babel/preset-env', $package['devDependencies'] ?? []);
        $this->assertArrayHasKey('babel-jest', $package['devDependencies'] ?? []);
        $this->assertArrayHasKey('jest', $package['devDependencies'] ?? []);
        $this->assertArrayHasKey('jest-environment-jsdom', $package['devDependencies'] ?? []);
        $jestConfigFile = $developmentDirectory . '/jest.config.cjs';
        $this->assertFileExists($jestConfigFile);
        $jestConfigSource = file_get_contents($jestConfigFile);
        $this->assertIsString($jestConfigSource);
        $this->assertStringContainsString('rootDir: ".."', $jestConfigSource);
        $this->assertStringContainsString('"<rootDir>/Development"', $jestConfigSource);
        $this->assertStringContainsString('"<rootDir>/JavaScript"', $jestConfigSource);
        $this->assertStringContainsString('babel-jest-transformer.cjs', $jestConfigSource);
        $this->assertFileExists($developmentDirectory . '/tests/setup.js');
        $this->assertFileExists($developmentDirectory . '/scripts/verify-esm-environment.js');
        $this->assertFileExists($developmentDirectory . '/babel-jest-transformer.cjs');

        $modulePackageFile = self::EXTENSION_ROOT . '/Resources/Public/JavaScript/package.json';
        $this->assertFileExists($modulePackageFile);
        $modulePackageSource = file_get_contents($modulePackageFile);
        $this->assertIsString($modulePackageSource);
        try {
            $modulePackage = json_decode($modulePackageSource, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            self::fail('Invalid JavaScript module package: ' . $exception->getMessage());
        }
        $this->assertIsArray($modulePackage);
        $this->assertSame('module', $modulePackage['type'] ?? null);
        $this->assertTrue($modulePackage['private'] ?? false);
    }

    #[Test]
    public function everyFrontendModuleAndExportedFunctionHasAJestSuite(): void
    {
        $moduleDirectory = self::EXTENSION_ROOT . '/Resources/Public/JavaScript/frontend';
        $testDirectory = self::EXTENSION_ROOT . '/Resources/Public/Development/tests';
        $modules = [
            $moduleDirectory . '/rich-text.js' => $testDirectory . '/ckeditor.test.js',
            $moduleDirectory . '/profile.js' => $testDirectory . '/profile.test.js',
        ];
        foreach (glob($moduleDirectory . '/profile/*.js') ?: [] as $module) {
            $modules[$module] = $testDirectory . '/' . basename($module, '.js') . '.test.js';
        }
        $this->assertCount(9, $modules);

        foreach ($modules as $module => $testFile) {
            $this->assertFileExists($module);
            $this->assertFileExists(
                $testFile,
                sprintf('JavaScript module "%s" has no dedicated Jest suite.', basename($module)),
            );
            $moduleSource = file_get_contents($module);
            $testSource = file_get_contents($testFile);
            $this->assertIsString($moduleSource);
            $this->assertIsString($testSource);
            preg_match_all(
                '/^export const ([A-Za-z_$][A-Za-z0-9_$]*)\s*=\s*(?:async\s*)?\(/m',
                $moduleSource,
                $matches,
            );
            foreach ($matches[1] as $functionName) {
                $this->assertStringContainsString(
                    $functionName,
                    $testSource,
                    sprintf(
                        'Exported JavaScript function "%s" is not referenced by "%s".',
                        $functionName,
                        basename($testFile),
                    ),
                );
            }
        }
    }
}
