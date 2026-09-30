<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The files `bin/set-version` writes are stored in the form its tools write them, so a
 * run with the version a branch already carries changes nothing.
 *
 * The tools regenerate what they touch. packwright reads `ext_emconf.php` into its data
 * and writes the file from that, so a comment is lost, and `extemconf:constraints:set`
 * moves the key it sets to the end of its list. `composer config` re-encodes the
 * `extra.typo3/cms` object of a manifest, and an empty `providesPackages` object comes
 * back as an array. `composer require` sorts `require` when the manifest asks for it.
 * Until ACE-787 such a run rewrote 22 files on `main`, and every release and branch cut
 * carried that into its commits.
 *
 * - No `ext_emconf.php` below `packages/fgtclb/` carries a comment. What one explains
 *   belongs in `docs/`.
 * - In `depends` and `suggests`, the keys of the academic extensions come last, in the
 *   order of their package directories, the order `bin/set-version` sets them in.
 * - A manifest `bin/set-version` writes with `composer config` has
 *   `"providesPackages": []`, which TYPO3 reads like `{}`.
 * - A manifest with `config.sort-packages` has `require` and `require-dev` in the order
 *   composer sorts them.
 */
final class SetVersionNormalFormTest extends TestCase
{
    /**
     * `Composer\Repository\PlatformRepository::PLATFORM_PACKAGE_REGEX` of composer 2.10.
     */
    private const PLATFORM_PACKAGE = '{^(?:php(?:-64bit|-ipv6|-zts|-debug)?|hhvm|(?:ext|lib)-[a-z0-9](?:[_.-]?[a-z0-9]+)*|composer(?:-(?:plugin|runtime)-api)?)$}iD';

    /**
     * @return \Generator<string, array{string}>
     */
    public static function extEmConfFilesDataProvider(): \Generator
    {
        $files = array_merge(
            glob(self::repositoryPath() . '/packages/fgtclb/*/ext_emconf.php') ?: [],
            glob(self::repositoryPath() . '/packages/fgtclb/*/Tests/Functional/Fixtures/Extensions/*/ext_emconf.php') ?: [],
        );
        sort($files);
        foreach ($files as $file) {
            yield self::relativePath($file) => [$file];
        }
    }

    #[DataProvider('extEmConfFilesDataProvider')]
    #[Test]
    public function extEmConfCarriesNoComment(string $file): void
    {
        $comments = [];
        foreach (token_get_all((string)file_get_contents($file)) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                $comments[] = 'line ' . $token[2] . ': ' . trim($token[1]);
            }
        }
        $this->assertSame([], $comments, 'packwright drops every comment the next time bin/set-version writes the file.');
    }

    #[DataProvider('extEmConfFilesDataProvider')]
    #[Test]
    public function academicConstraintsComeLastInPackageOrder(string $file): void
    {
        $academicKeys = self::academicExtensionKeys();
        $constraints = self::extEmConf($file)['constraints'] ?? [];
        foreach (['depends', 'suggests'] as $type) {
            $keys = array_map('strval', array_keys(is_array($constraints[$type] ?? null) ? $constraints[$type] : []));
            $academic = array_values(array_intersect($academicKeys, $keys));
            $this->assertSame(
                $academic,
                array_slice($keys, count($keys) - count($academic)),
                sprintf('The academic keys of "%s" are not last in package order, bin/set-version would move them.', $type),
            );
        }
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function versionedManifestsDataProvider(): \Generator
    {
        $files = array_merge(
            glob(self::repositoryPath() . '/packages/fgtclb/*/composer.json') ?: [],
            glob(self::repositoryPath() . '/packages-dev/*/composer.json') ?: [],
        );
        sort($files);
        foreach ($files as $file) {
            yield self::relativePath($file) => [$file];
        }
    }

    #[DataProvider('versionedManifestsDataProvider')]
    #[Test]
    public function providesPackagesIsWrittenAsComposerWritesIt(string $file): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/"providesPackages"\s*:\s*\{\s*\}/',
            (string)file_get_contents($file),
            'composer config writes an empty "providesPackages" as [].',
        );
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function sortedManifestsDataProvider(): \Generator
    {
        $files = array_merge(
            [self::repositoryPath() . '/composer.json'],
            glob(self::repositoryPath() . '/core-*/composer.json') ?: [],
            glob(self::repositoryPath() . '/packages/fgtclb/*/composer.json') ?: [],
            glob(self::repositoryPath() . '/packages-dev/*/composer.json') ?: [],
        );
        sort($files);
        foreach ($files as $file) {
            if ((self::manifest($file)['config']['sort-packages'] ?? false) === true) {
                yield self::relativePath($file) => [$file];
            }
        }
    }

    #[DataProvider('sortedManifestsDataProvider')]
    #[Test]
    public function requirementsAreInComposerOrder(string $file): void
    {
        $manifest = self::manifest($file);
        foreach (['require', 'require-dev'] as $section) {
            $names = array_map('strval', array_keys(is_array($manifest[$section] ?? null) ? $manifest[$section] : []));
            $sorted = $names;
            usort($sorted, static fn(string $a, string $b): int => strnatcmp(self::sortKey($a), self::sortKey($b)));
            $this->assertSame($sorted, $names, sprintf('"%s" is not sorted, composer require would sort it.', $section));
        }
    }

    /**
     * The key composer sorts a requirement by (`JsonManipulator::sortPackages()`):
     * platform packages first, in the order php, hhvm, ext-*, lib-*, the others.
     */
    private static function sortKey(string $name): string
    {
        if (preg_match(self::PLATFORM_PACKAGE, $name) === 1) {
            return (string)preg_replace(['/^php/', '/^hhvm/', '/^ext/', '/^lib/', '/^\D/'], ['0-$0', '1-$0', '2-$0', '3-$0', '4-$0'], $name);
        }
        return '5-' . $name;
    }

    /**
     * The extension keys of the packages below `packages/fgtclb/`, in the order
     * `bin/set-version` discovers them.
     *
     * @return list<string>
     */
    private static function academicExtensionKeys(): array
    {
        $directories = glob(self::repositoryPath() . '/packages/fgtclb/*', GLOB_ONLYDIR) ?: [];
        sort($directories);
        $keys = [];
        foreach ($directories as $directory) {
            if (!is_file($directory . '/ext_emconf.php') || !is_file($directory . '/composer.json')) {
                continue;
            }
            $key = self::manifest($directory . '/composer.json')['extra']['typo3/cms']['extension-key'] ?? null;
            if (is_string($key)) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function extEmConf(string $file): array
    {
        $_EXTKEY = basename(dirname($file));
        $EM_CONF = [];
        include $file;
        $configuration = $EM_CONF[$_EXTKEY] ?? [];
        return is_array($configuration) ? $configuration : [];
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function manifest(string $file): array
    {
        $manifest = json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        return is_array($manifest) ? $manifest : [];
    }

    private static function relativePath(string $file): string
    {
        return substr($file, strlen(self::repositoryPath()) + 1);
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
