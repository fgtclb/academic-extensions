<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every package of this repository points the links of its rendered manual at itself and names
 * its manual for other manuals.
 *
 * The renderer reads `Documentation/guides.xml` of a package, and docs.typo3.org builds
 * three links of every page from it: "Edit on GitHub" from `edit-on-github`,
 * `edit-on-github-branch` and `edit-on-github-directory`, the repository link from
 * `project-repository` and the extension link from `project-home`. The files were copied
 * from one another, and a copy that kept the directory of academic_base sent every edit
 * link of the category_types and academic_study_plan manuals into the academic_base
 * manual until ACE-773. Nothing else reports that: the render succeeds, and the links only
 * fail when somebody follows them.
 *
 * - The edit link names this mono repository and the package's own `Documentation/`
 *   directory, because the split repositories are read-only.
 * - The edit branch is the branch the files live on, so that the manual of a version
 *   line is edited where that version line is maintained. It is read from the only
 *   `extra.branch-alias` of the root `composer.json`, whose key is the version name composer
 *   gives the branch (`dev-main`, `2.x-dev`). `bin/set-version` writes both from the same
 *   branch name.
 * - The repository link names the split repository, which is named after the package
 *   directory, not after the composer package: `fgtclb/typo3-category-types`.
 * - The extension link names the extension key.
 * - The interlink shortcode is the composer name of the package. It gives the manual its
 *   permalinks, and other manuals link to it through it. The renderer logs a warning for a
 *   manual without one since its release of October 2026, which fails every render that
 *   runs with `--fail-on-log`, the documentation job of CI among them.
 * - No `guides.xml` sits outside `Documentation/`, where the renderer never reads it and
 *   `bin/set-version` never updates it.
 */
final class DocumentationGuidesTest extends TestCase
{
    private const MONO_REPOSITORY = 'fgtclb/academic-extensions';

    /**
     * @return \Generator<string, array{string}>
     */
    public static function packageDirectoriesDataProvider(): \Generator
    {
        foreach (self::packageDirectories() as $directory) {
            yield basename($directory) => [$directory];
        }
    }

    #[DataProvider('packageDirectoriesDataProvider')]
    #[Test]
    public function linksOfTheManualNameThePackage(string $directory): void
    {
        $attributes = self::themeAttributes($directory);
        $packageDirectory = basename($directory);
        $extensionKey = self::extensionKey($directory);
        $this->assertNotSame('', $extensionKey, 'composer.json declares no "extra.typo3/cms.extension-key".');
        $composerName = self::composerName($directory);
        $this->assertNotSame('', $composerName, 'composer.json declares no "name".');

        $this->assertSame(
            [
                'edit-on-github' => self::MONO_REPOSITORY,
                'edit-on-github-branch' => self::branch(),
                'edit-on-github-directory' => 'packages/fgtclb/' . $packageDirectory . '/Documentation',
                'project-repository' => 'https://github.com/fgtclb/' . $packageDirectory,
                'project-home' => 'https://extensions.typo3.org/extension/' . $extensionKey . '/',
                'interlink-shortcode' => $composerName,
            ],
            [
                'edit-on-github' => $attributes['edit-on-github'] ?? null,
                'edit-on-github-branch' => $attributes['edit-on-github-branch'] ?? null,
                'edit-on-github-directory' => $attributes['edit-on-github-directory'] ?? null,
                'project-repository' => $attributes['project-repository'] ?? null,
                'project-home' => $attributes['project-home'] ?? null,
                'interlink-shortcode' => $attributes['interlink-shortcode'] ?? null,
            ],
        );
    }

    #[Test]
    public function noGuidesXmlOutsideTheDocumentationDirectory(): void
    {
        $misplaced = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::repositoryPath() . '/packages', \FilesystemIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getFilename() === 'guides.xml' && basename($file->getPath()) !== 'Documentation') {
                $misplaced[] = substr($file->getPathname(), strlen(self::repositoryPath()) + 1);
            }
        }
        $this->assertSame([], $misplaced, 'The renderer reads Documentation/guides.xml only.');
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }

    /**
     * The branch this checkout belongs to, from the one branch alias of the root
     * `composer.json`. Its key is the version name composer gives the branch: `dev-main`
     * for `main`, `2.x-dev` for `2`, `2.2.x-dev` for `2.2`. `2.x-dev` would also be the
     * name of a branch `2.x`, but every version branch here is named by its bare number.
     */
    public static function branch(): string
    {
        $manifest = json_decode(
            (string)file_get_contents(self::repositoryPath() . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $aliases = is_array($manifest) ? ($manifest['extra']['branch-alias'] ?? []) : [];
        $keys = is_array($aliases) ? array_keys($aliases) : [];
        if (count($keys) !== 1) {
            throw new \RuntimeException('The root composer.json needs exactly one branch alias.', 1790668801);
        }
        $key = (string)$keys[0];
        if (str_starts_with($key, 'dev-')) {
            return substr($key, 4);
        }
        if (preg_match('/^(\d+(?:\.\d+){0,2})\.x-dev$/', $key, $matches) === 1) {
            return $matches[1];
        }
        throw new \RuntimeException(
            sprintf('The branch alias key "%s" of the root composer.json is no version name composer gives a branch.', $key),
            1790795892,
        );
    }

    /**
     * Every package below `packages/fgtclb/` with a manual.
     *
     * @return list<string>
     */
    private static function packageDirectories(): array
    {
        $directories = array_map(
            'dirname',
            glob(self::repositoryPath() . '/packages/fgtclb/*/Documentation', GLOB_ONLYDIR) ?: [],
        );
        sort($directories);
        return $directories;
    }

    /**
     * The attributes of the theme extension element of `Documentation/guides.xml`.
     *
     * @return array<string, string>
     */
    private static function themeAttributes(string $directory): array
    {
        $document = new \DOMDocument();
        if (!$document->load($directory . '/Documentation/guides.xml')) {
            return [];
        }
        $attributes = [];
        foreach ($document->getElementsByTagName('extension') as $element) {
            foreach ($element->attributes ?? [] as $attribute) {
                $attributes[$attribute->nodeName] = (string)$attribute->nodeValue;
            }
        }
        return $attributes;
    }

    private static function extensionKey(string $directory): string
    {
        $manifest = json_decode((string)file_get_contents($directory . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $key = is_array($manifest) ? ($manifest['extra']['typo3/cms']['extension-key'] ?? '') : '';
        return is_string($key) ? $key : '';
    }

    private static function composerName(string $directory): string
    {
        $manifest = json_decode((string)file_get_contents($directory . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $name = is_array($manifest) ? ($manifest['name'] ?? '') : '';
        return is_string($name) ? $name : '';
    }
}
