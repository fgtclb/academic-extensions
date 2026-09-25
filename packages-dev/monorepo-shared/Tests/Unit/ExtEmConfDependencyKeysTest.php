<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every key in the `constraints` of an `ext_emconf.php` of this repository names an
 * extension that exists.
 *
 * A classic, non-Composer installation resolves the dependencies of an extension from
 * those keys. A key that is no extension key - `rte-ckeditor` instead of `rte_ckeditor`,
 * as academic_jobs carried from 2.1.0 to ACE-731 - is a dependency on a package that
 * does not exist, and nothing else reports it: Composer installations and TYPO3 v14 read
 * `composer.json` instead, and the testing framework skips a name that is no extension
 * key silently.
 *
 * The keys are not compared with the `composer.json` of the extension: three extensions
 * name a core extension there and not here, or the other way round, and are right to.
 * What is valid is read from the installed packages instead, so a new core version or a
 * new extension needs no change here:
 *
 * - `typo3` and `php`, which are no extension keys but constraints of their own;
 * - the extension key of every installed TYPO3 package, core extensions and the
 *   packages of this repository included;
 * - for a fixture extension, additionally the key of every fixture extension, which the
 *   test instances load by path rather than through Composer;
 * - for `suggests` and `conflicts`, an extension of {@see self::NOT_INSTALLED} that the
 *   `composer.json` of the extension names in its `suggest` or `conflict` section.
 */
final class ExtEmConfDependencyKeysTest extends TestCase
{
    /**
     * Suggested extensions the development setup does not install, by extension key.
     *
     * `fgtclb/page-backend-layout` 2.x supports TYPO3 v12 and v13 only, so it cannot be
     * installed into the one dependency set that serves TYPO3 v13 and v14. An entry here is
     * accepted only where the extension's `composer.json` names the package in the same
     * kind of constraint, and fails once the package is installed.
     */
    private const NOT_INSTALLED = [
        'page_backend_layout' => 'fgtclb/page-backend-layout',
    ];

    /**
     * The `composer.json` section that corresponds to an `ext_emconf.php` constraint.
     */
    private const COMPOSER_SECTIONS = [
        'depends' => 'require',
        'suggests' => 'suggest',
        'conflicts' => 'conflict',
    ];

    /**
     * @return \Generator<string, array{string}>
     */
    public static function extensionDirectoriesDataProvider(): \Generator
    {
        foreach (self::extensionDirectories() as $directory) {
            yield substr($directory, strlen(self::repositoryPath()) + 1) => [$directory];
        }
    }

    #[DataProvider('extensionDirectoriesDataProvider')]
    #[Test]
    public function everyConstraintNamesAnExistingExtension(string $directory): void
    {
        $composerManifest = self::readComposerManifest($directory);
        $extensionKey = self::extensionKey($composerManifest);
        $this->assertNotSame('', $extensionKey, 'composer.json declares no "extra.typo3/cms.extension-key".');

        $validKeys = ['typo3', 'php', ...self::installedExtensionKeys()];
        if (self::isFixtureExtension($directory)) {
            $validKeys = [...$validKeys, ...self::fixtureExtensionKeys()];
        }
        $emConf = self::readExtEmConf($directory, $extensionKey);
        $this->assertNotNull($emConf, sprintf('ext_emconf.php declares nothing for "%s".', $extensionKey));

        $invalid = [];
        foreach ($emConf['constraints'] ?? [] as $type => $constraints) {
            $this->assertArrayHasKey($type, self::COMPOSER_SECTIONS, sprintf('Unknown constraint type "%s".', $type));
            foreach (array_keys($constraints) as $key) {
                if (in_array($key, $validKeys, true)) {
                    continue;
                }
                if ($type !== 'depends'
                    && isset(self::NOT_INSTALLED[$key])
                    && isset($composerManifest[self::COMPOSER_SECTIONS[$type]][self::NOT_INSTALLED[$key]])
                ) {
                    continue;
                }
                $invalid[] = $type . ': ' . $key;
            }
        }

        $this->assertSame(
            [],
            $invalid,
            'ext_emconf.php names no installed extension by these keys. A constraint names an extension key -'
            . ' "rte_ckeditor", not "rte-ckeditor" or "typo3/cms-rte-ckeditor".',
        );
    }

    #[Test]
    public function notInstalledListNamesNoInstalledExtension(): void
    {
        $this->assertSame(
            [],
            array_values(array_intersect(array_keys(self::NOT_INSTALLED), self::installedExtensionKeys())),
            'Installed now - remove them from ' . self::class . '::NOT_INSTALLED.',
        );
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }

    /**
     * Every directory below `packages/` holding an `ext_emconf.php`: the extensions and
     * their fixture extensions.
     *
     * @return list<string>
     */
    private static function extensionDirectories(): array
    {
        $directories = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::repositoryPath() . '/packages', \FilesystemIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getFilename() === 'ext_emconf.php') {
                $directories[] = $file->getPath();
            }
        }
        sort($directories);
        return $directories;
    }

    private static function isFixtureExtension(string $directory): bool
    {
        return str_contains(substr($directory, strlen(self::repositoryPath())), '/Tests/');
    }

    /**
     * @return list<string>
     */
    private static function fixtureExtensionKeys(): array
    {
        $keys = [];
        foreach (self::extensionDirectories() as $directory) {
            if (self::isFixtureExtension($directory)) {
                $keys[] = self::extensionKey(self::readComposerManifest($directory));
            }
        }
        return $keys;
    }

    /**
     * @return list<string>
     */
    private static function installedExtensionKeys(): array
    {
        $keys = [];
        foreach (['typo3-cms-framework', 'typo3-cms-extension'] as $packageType) {
            foreach (InstalledVersions::getInstalledPackagesByType($packageType) as $packageName) {
                $installPath = InstalledVersions::getInstallPath($packageName);
                if ($installPath !== null) {
                    $keys[] = self::extensionKey(self::readComposerManifest($installPath));
                }
            }
        }
        return array_values(array_unique(array_filter($keys, static fn(string $key): bool => $key !== '')));
    }

    /**
     * @return array<string, mixed>
     */
    private static function readComposerManifest(string $directory): array
    {
        $manifest = json_decode((string)file_get_contents($directory . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        return is_array($manifest) ? $manifest : [];
    }

    /**
     * @param array<string, mixed> $composerManifest
     */
    private static function extensionKey(array $composerManifest): string
    {
        $extra = $composerManifest['extra'] ?? [];
        $key = is_array($extra) && is_array($extra['typo3/cms'] ?? null) ? ($extra['typo3/cms']['extension-key'] ?? '') : '';
        return is_string($key) ? $key : '';
    }

    /**
     * @return array{constraints?: array<string, array<string, string>>}|null
     */
    private static function readExtEmConf(string $directory, string $extensionKey): ?array
    {
        return (static function (string $file, string $_EXTKEY): ?array {
            $EM_CONF = [];
            require $file;
            return $EM_CONF[$_EXTKEY] ?? null;
        })($directory . '/ext_emconf.php', $extensionKey);
    }
}
