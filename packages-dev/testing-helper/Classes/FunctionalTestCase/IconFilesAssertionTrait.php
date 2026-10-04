<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Asserts the icon files an extension ships below `Resources/Public/Icons/`: each one is
 * drawn by a registered icon, and each one is attributed in the licence notice.
 *
 * Both checks are about files, not about one registry. A file of the shared set of
 * `academic_base` may be drawn by an action icon of the frontend registry, by a record
 * icon another extension registers in the backend registry, by a category type icon
 * that reaches both, or by all of them, so the orphan check asks both registries.
 * {@see ColourSchemeAwareIconsTrait} and {@see FrontendIconsAssertionTrait} check the
 * registrations of one registry each.
 */
trait IconFilesAssertionTrait
{
    /**
     * An icon file nothing registers is covered by no other assertion: every other check
     * walks registrations, and an orphan is not one. The day it gets registered it has
     * never been looked at. So every SVG file below `Resources/Public/Icons/` of the
     * extension has to be the source of at least one identifier of the backend or the
     * frontend registry, registered by any loaded package, because a shared file may be
     * drawn by an icon of another extension. Category type and group icons count like
     * every other icon: `EXT:category_types` registers both, under
     * `category_types.<group>.<type>` and `category_types_group.<group>`.
     *
     * The exempt files have to exist: `Extension.svg` is the icon of the extension
     * manager and the TER, which read the file rather than a registry.
     *
     * @param list<string> $exemptFiles paths relative to `Resources/Public/Icons/`
     */
    private function assertEveryIconFileIsRegistered(string $extensionKey, array $exemptFiles = ['Extension.svg']): void
    {
        $iconDirectory = $this->getIconDirectoryOfExtension($extensionKey);
        foreach ($exemptFiles as $exemptFile) {
            self::assertFileExists($iconDirectory . $exemptFile);
        }

        $sourcePrefix = 'EXT:' . $extensionKey . '/Resources/Public/Icons/';
        $registeredFiles = [];
        $iconRegistry = $this->get(IconRegistry::class);
        foreach ($iconRegistry->getAllRegisteredIconIdentifiers() as $identifier) {
            // Asking for the configuration of a deprecated icon raises E_USER_DEPRECATED.
            if ($iconRegistry->isDeprecated($identifier)) {
                continue;
            }
            $source = (string)($iconRegistry->getIconConfigurationByIdentifier($identifier)['options']['source'] ?? '');
            if (str_starts_with($source, $sourcePrefix)) {
                $registeredFiles[substr($source, strlen($sourcePrefix))][] = $identifier;
            }
        }
        $frontendIconRegistry = $this->get(FrontendIconRegistry::class);
        foreach ($frontendIconRegistry->getAllRegisteredIconIdentifiers() as $identifier) {
            $source = (string)($frontendIconRegistry->getIconConfiguration($identifier)['options']['source'] ?? '');
            if (str_starts_with($source, $sourcePrefix)) {
                $registeredFiles[substr($source, strlen($sourcePrefix))][] = $identifier;
            }
        }

        $files = $this->getSvgFilesBelow($iconDirectory);
        self::assertNotSame([], $files, sprintf('EXT:%s ships no icon file - the check asserted nothing.', $extensionKey));
        foreach ($files as $file) {
            if (in_array($file, $exemptFiles, true)) {
                continue;
            }
            self::assertArrayHasKey(
                $file,
                $registeredFiles,
                sprintf(
                    '%s%s is the source of no icon of the backend or the frontend registry. Register it or delete it.',
                    $sourcePrefix,
                    $file,
                ),
            );
        }
    }

    /**
     * Font Awesome Free icons are CC BY 4.0, and the attribution comment in the file never
     * reaches the page, the sanitiser removes comments. The attribution is the notice file
     * next to the icons, which lists every file it covers with its Font Awesome name, so a
     * file added without being listed there ships without its licence. Every SVG file
     * apart from the exempt ones carries the Font Awesome Free comment and is listed in
     * the notice, and the notice lists no file that does not exist.
     *
     * @param list<string> $exemptFiles paths relative to `Resources/Public/Icons/`
     */
    private function assertEveryIconFileIsAttributedInTheNotice(
        string $extensionKey,
        array $exemptFiles = ['Extension.svg'],
        string $noticeFile = 'LICENSE-font-awesome.txt',
    ): void {
        $iconDirectory = $this->getIconDirectoryOfExtension($extensionKey);
        self::assertFileExists($iconDirectory . $noticeFile);
        $notice = (string)file_get_contents($iconDirectory . $noticeFile);

        $files = array_values(array_diff($this->getSvgFilesBelow($iconDirectory), $exemptFiles));
        self::assertNotSame([], $files, sprintf('EXT:%s ships no icon file - the check asserted nothing.', $extensionKey));
        foreach ($files as $file) {
            self::assertMatchesRegularExpression(
                '/^\s+' . preg_quote($file, '/') . '\s+[a-z0-9-]+$/m',
                $notice,
                sprintf('%s is not listed in %s with its Font Awesome name.', $file, $noticeFile),
            );
            self::assertStringContainsString(
                '<!--! Font Awesome Free ',
                (string)file_get_contents($iconDirectory . $file),
                sprintf('%s does not carry the Font Awesome Free attribution comment.', $file),
            );
        }

        preg_match_all('/^\s+(\S+\.svg)\s+[a-z0-9-]+$/m', $notice, $listed);
        foreach ($listed[1] as $listedFile) {
            self::assertContains(
                $listedFile,
                $files,
                sprintf('%s lists %s, which the extension does not ship.', $noticeFile, $listedFile),
            );
        }
    }

    private function getIconDirectoryOfExtension(string $extensionKey): string
    {
        $iconDirectory = $this->get(PackageManager::class)->getPackage($extensionKey)->getPackagePath()
            . 'Resources/Public/Icons/';
        self::assertDirectoryExists($iconDirectory);

        return $iconDirectory;
    }

    /**
     * @return list<string> paths relative to `$directory`, sorted
     */
    private function getSvgFilesBelow(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && strtolower($file->getExtension()) === 'svg') {
                $files[] = substr($file->getPathname(), strlen($directory));
            }
        }
        sort($files);

        return $files;
    }
}
