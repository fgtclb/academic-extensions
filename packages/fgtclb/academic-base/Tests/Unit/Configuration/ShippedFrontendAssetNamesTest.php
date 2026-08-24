<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * No frontend asset of this repository is called "ckeditor.js".
 *
 * That name is not free. CKEditor 4 has no idea where it was installed, so it
 * derives its own base path by walking the script tags of the document and
 * taking the first "src" that matches
 *
 *     /(^|.*[\\\/])ckeditor\.js(?:\?.*|;.*)?$/i
 *
 * stopping at the first hit. TYPO3 renders an import mapped module before the
 * plain script assets of a page, so a module of that name is found before the
 * one loaded from the content delivery network, and the editor then looks for
 * its skin and its language files below the extension that shipped the module.
 * They answer 404 there, "CKEDITOR.lang" stays undefined and the rich text
 * fields of the frontend forms of "academic_jobs" and "academic_persons_edit"
 * silently stay plain textareas - which is exactly what happened between
 * ACE-392 and ACE-469.
 *
 * The pattern is case insensitive and allows a query string, so neither the
 * "CKEditor.js" spelling the extensions used before nor the "?bust=" cache key
 * TYPO3 appends is a way around it.
 *
 * The one legitimate reason to ship a file of that name would be vendoring
 * CKEditor itself, which nothing here does. Doing so would make this test fail,
 * and that is the right moment to think about base path detection again rather
 * than to loosen the assertion.
 */
final class ShippedFrontendAssetNamesTest extends UnitTestCase
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
     * The file names no shipped asset may carry, lower cased.
     *
     * The TypeScript source is listed as well, because the build derives the
     * name of the artifact from it one to one - so a source named "ckeditor.ts"
     * reintroduces the defect at the next build rather than at review time.
     *
     * @var list<string>
     */
    private const FORBIDDEN_NAMES = ['ckeditor.js', 'ckeditor.ts'];

    #[Test]
    public function noShippedFrontendAssetIsNamedAfterTheEditorItself(): void
    {
        $scanRoot = $this->determineScanRoot();
        $assets = $this->collectFrontendAssets($scanRoot);

        $this->assertNotSame([], $assets, sprintf('No frontend asset found below "%s".', $scanRoot));

        $offending = array_values(array_filter(
            $assets,
            static fn(string $file): bool => in_array(strtolower(basename($file)), self::FORBIDDEN_NAMES, true),
        ));

        $this->assertSame(
            [],
            $offending,
            sprintf(
                "A frontend asset is named after CKEditor itself, which takes the editor's base path"
                . " away from the content delivery network:\n - %s\n\nRename it - \"rich-text\" is what"
                . ' the two extensions doing this use - and address the module under the new name in the'
                . ' template and in the changelog entry.',
                implode("\n - ", array_map(
                    static fn(string $file): string => substr($file, strlen($scanRoot) + 1),
                    $offending,
                )),
            ),
        );
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
     * Every TypeScript source and every published script of every extension below the root.
     *
     * @return list<string> absolute file names, sorted, so that a failure message is stable
     */
    private function collectFrontendAssets(string $root): array
    {
        $directories = new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS);
        $filtered = new \RecursiveCallbackFilterIterator(
            $directories,
            static function (\SplFileInfo $current): bool {
                if ($current->isDir()) {
                    return !in_array($current->getFilename(), self::SKIPPED_DIRECTORIES, true);
                }

                return in_array(strtolower($current->getExtension()), ['js', 'ts'], true);
            },
        );

        $files = [];
        foreach (new \RecursiveIteratorIterator($filtered) as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            if (str_contains($path, '/Resources/Private/TypeScript/') || str_contains($path, '/Resources/Public/JavaScript/')) {
                $files[] = $path;
            }
        }
        sort($files);

        return $files;
    }
}
