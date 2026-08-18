<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Parses every YAML file shipped by the extensions of this repository.
 *
 * Most of them are never loaded by a test: a route enhancer is read only when a
 * site configuration imports it, a set configuration only when a site enables the
 * set. "academic-programs/Configuration/Yaml/Routes.yaml" was therefore indented
 * with tabs - which YAML forbids outright - through several releases without a
 * single gate noticing (ACE-453).
 *
 * This test closes that whole class of defect for the cost of one file read each.
 * It deliberately only parses: what a file means is the business of the component
 * that consumes it, whether it is syntactically YAML at all is not.
 *
 * An empty file is valid YAML and parses to NULL. That is not a failure - there is
 * such a file in this repository on purpose, as a fixture of "category_types".
 */
final class ShippedYamlFilesTest extends UnitTestCase
{
    /**
     * Directory names that are never a source: generated, installed or vendored trees.
     *
     * @var list<string>
     */
    private const SKIPPED_DIRECTORIES = [
        '.Build',
        '.git',
        'Documentation-GENERATED-temp',
        'node_modules',
        'public',
        'var',
        'vendor',
    ];

    /**
     * @var list<string>
     */
    private const YAML_EXTENSIONS = ['yaml', 'yml'];

    #[Test]
    public function everyShippedYamlFileIsSyntacticallyValid(): void
    {
        $scanRoot = $this->determineScanRoot();
        $files = $this->collectYamlFiles($scanRoot);

        $this->assertNotSame([], $files, sprintf('No YAML file found below "%s".', $scanRoot));

        $failures = [];
        foreach ($files as $file) {
            try {
                Yaml::parseFile($file);
            } catch (ParseException $exception) {
                $failures[] = sprintf(
                    ' - %s: %s',
                    substr($file, strlen($scanRoot) + 1),
                    $exception->getMessage(),
                );
            }
        }

        $this->assertSame(
            [],
            $failures,
            sprintf(
                "%d of %d shipped YAML files below \"%s\" are not valid YAML:\n%s",
                count($failures),
                count($files),
                $scanRoot,
                implode("\n", $failures),
            ),
        );
    }

    /**
     * Pins the assumption the scan above rests on, so that an empty file is never
     * "fixed" into a failure.
     */
    #[Test]
    public function anEmptyYamlFileParsesToNullInsteadOfFailing(): void
    {
        $this->assertNull(Yaml::parse(''));
    }

    /**
     * The mono repository keeps all extensions below "packages/", and that is what
     * has to be covered. The split-off read-only repository of this extension has no
     * such directory - there the extension itself is the whole repository.
     */
    private function determineScanRoot(): string
    {
        $extensionRoot = dirname(__DIR__, 3);
        $packagesRoot = dirname($extensionRoot, 2);

        if (basename($packagesRoot) === 'packages' && is_dir($packagesRoot)) {
            return $packagesRoot;
        }

        return $extensionRoot;
    }

    /**
     * @return list<string> absolute file names, sorted, so that a failure message is stable
     */
    private function collectYamlFiles(string $root): array
    {
        $directories = new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS);
        $filtered = new \RecursiveCallbackFilterIterator(
            $directories,
            static function (\SplFileInfo $current): bool {
                if ($current->isDir()) {
                    return !in_array($current->getFilename(), self::SKIPPED_DIRECTORIES, true);
                }

                return in_array(strtolower($current->getExtension()), self::YAML_EXTENSIONS, true);
            },
        );

        $files = [];
        foreach (new \RecursiveIteratorIterator($filtered) as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
