<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Upgrade;

/**
 * Compares the Fluid files of one override folder with those of one upstream
 * folder.
 *
 * The checker knows nothing about extensions, sites or the console: it takes two
 * absolute folders and answers, per file of the first one, whether the second
 * one still holds a file at the same relative path and whether the two differ.
 * {@see \FGTCLB\AcademicBase\Command\UpgradeCheckCommand} resolves the folders and
 * prints the result.
 *
 * The upstream index is built by reading the directories rather than by asking
 * the file system for one path at a time, so the case comparison holds on a case
 * insensitive file system as well - which is the one the case finding exists for.
 */
final class TemplateOverrideChecker
{
    /**
     * The file extension of every Fluid file the academic extensions ship.
     *
     * A project override folder holds more than templates - XLIFF files, images,
     * a `README` - and none of those is resolved through a view root path.
     *
     * Note that `Name.fluid.html` is deliberately not treated as the same
     * template as `Name.html`: Fluid 5 (TYPO3 v14) resolves it, Fluid 4 (TYPO3
     * v13) does not, so on TYPO3 v13 such an override really is dead and
     * reporting it is the point of the check.
     */
    private const FLUID_FILE_EXTENSION = 'html';

    /**
     * @param string $overrideFolder Absolute path of the folder holding the project's files.
     * @param string $upstreamFolder Absolute path of the folder of the extension it overrides.
     *                               A folder that does not exist is treated as an empty one.
     * @return list<TemplateOverrideFinding> One finding per override file that is worth reporting,
     *                                       ordered by the relative path.
     */
    public function check(string $overrideFolder, string $upstreamFolder): array
    {
        $overrideFolder = rtrim($overrideFolder, '/') . '/';
        $upstreamFolder = rtrim($upstreamFolder, '/') . '/';

        $upstreamFiles = $this->fluidFilesOf($upstreamFolder);
        $upstreamByLowerCasePath = [];
        foreach ($upstreamFiles as $relativePath) {
            // A folder holding two files whose names differ only in case exists on Linux
            // only, and picking the first one keeps the result independent of the read
            // order of the directory.
            $upstreamByLowerCasePath[strtolower($relativePath)] ??= $relativePath;
        }
        $upstreamFiles = array_flip($upstreamFiles);

        $findings = [];
        foreach ($this->fluidFilesOf($overrideFolder) as $relativePath) {
            if (isset($upstreamFiles[$relativePath])) {
                if (!$this->isIdentical($overrideFolder . $relativePath, $upstreamFolder . $relativePath)) {
                    continue;
                }
                $findings[] = new TemplateOverrideFinding(
                    TemplateOverrideFindingKind::Identical,
                    $relativePath,
                    $relativePath,
                );
                continue;
            }
            $upstreamPath = $upstreamByLowerCasePath[strtolower($relativePath)] ?? null;
            if ($upstreamPath !== null) {
                $findings[] = new TemplateOverrideFinding(
                    TemplateOverrideFindingKind::CaseMismatch,
                    $relativePath,
                    $upstreamPath,
                );
                continue;
            }
            $findings[] = new TemplateOverrideFinding(
                TemplateOverrideFindingKind::MissingUpstream,
                $relativePath,
            );
        }

        return $findings;
    }

    /**
     * @return list<string> The Fluid files below the folder, relative to it and sorted.
     */
    private function fluidFilesOf(string $folder): array
    {
        if (!is_dir($folder)) {
            return [];
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS),
        );
        $files = [];
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== self::FLUID_FILE_EXTENSION) {
                continue;
            }
            $files[] = substr($file->getPathname(), strlen($folder));
        }
        sort($files, SORT_STRING);

        return $files;
    }

    private function isIdentical(string $overrideFile, string $upstreamFile): bool
    {
        if (filesize($overrideFile) !== filesize($upstreamFile)) {
            return false;
        }

        return file_get_contents($overrideFile) === file_get_contents($upstreamFile);
    }
}
