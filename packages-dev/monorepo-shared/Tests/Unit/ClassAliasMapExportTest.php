<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every class alias map a package declares ships with the package.
 *
 * A package names its maps in `extra.typo3/class-alias-loader.class-alias-maps`. Composer
 * installs a split package from the GitHub archive of its split repository, and that
 * archive leaves out every path the package's `.gitattributes` marks `export-ignore`.
 * Several packages mark `/Migrations` that way. A map below it is then missing from every
 * composer installation, the class alias loader only reports the missing file, and the
 * deprecated class names it was meant to keep stop resolving. Nothing in this repository
 * notices, because the packages are installed from their directories here.
 */
final class ClassAliasMapExportTest extends TestCase
{
    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function classAliasMapsDataProvider(): \Generator
    {
        $directories = array_map('dirname', glob(self::repositoryPath() . '/packages/fgtclb/*/composer.json') ?: []);
        sort($directories);
        foreach ($directories as $directory) {
            $manifest = json_decode((string)file_get_contents($directory . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            $maps = is_array($manifest) ? ($manifest['extra']['typo3/class-alias-loader']['class-alias-maps'] ?? []) : [];
            foreach (is_array($maps) ? $maps : [] as $map) {
                yield basename($directory) . ': ' . $map => [$directory, (string)$map];
            }
        }
    }

    #[Test]
    public function atLeastOnePackageDeclaresAClassAliasMap(): void
    {
        $this->assertNotSame([], iterator_to_array(self::classAliasMapsDataProvider()));
    }

    #[DataProvider('classAliasMapsDataProvider')]
    #[Test]
    public function theMapExistsAndIsNotExportIgnored(string $directory, string $map): void
    {
        $this->assertFileExists($directory . '/' . $map);
        $ignored = [];
        $attributes = is_file($directory . '/.gitattributes') ? file($directory . '/.gitattributes', FILE_IGNORE_NEW_LINES) : [];
        foreach ($attributes ?: [] as $line) {
            $parts = preg_split('/\s+/', trim($line)) ?: [];
            if (count($parts) < 2 || !in_array('export-ignore', array_slice($parts, 1), true)) {
                continue;
            }
            $pattern = '/' . trim($parts[0], '/');
            if ($pattern === '/' . $map || str_starts_with('/' . $map, $pattern . '/')) {
                $ignored[] = $line;
            }
        }
        $this->assertSame([], $ignored, sprintf('The archive of %s leaves out %s.', basename($directory), $map));
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
