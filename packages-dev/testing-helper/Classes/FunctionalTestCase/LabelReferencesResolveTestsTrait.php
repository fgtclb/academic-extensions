<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolves every label reference of the TCA and of the FlexForms it names.
 *
 * A label reference that names a missing file or a missing key does not raise
 * anything: `LanguageService::sL()` returns an empty string, and the backend
 * renders a field, a tab, an item or a palette without a label. Nobody notices
 * until somebody looks at that one form, on that one core version. The core
 * labels move between versions, and a file of another extension resolves only
 * while that extension is loaded, so a reference that worked once can stop
 * working without a line of this repository changing.
 *
 * The test instance loads the extension and what it requires, nothing else.
 * A reference into an extension that is not a declared dependency therefore
 * fails here, although it resolves in an installation that happens to have
 * both.
 *
 * Every string of `$GLOBALS['TCA']` is searched, which covers `label`,
 * `description`, items, `showitem` overrides (`field;LLL:...`, `--div--;LLL:...`)
 * and palettes alike. A string that names a FlexForm file (`FILE:EXT:...`) is
 * read and searched the same way, so the FlexForm the running core version uses
 * is checked on that core version.
 */
trait LabelReferencesResolveTestsTrait
{
    #[Test]
    public function everyLabelReferenceOfTheTcaAndItsFlexFormsResolves(): void
    {
        $references = [];
        $unreadableFiles = [];
        $this->collectLabelReferences($GLOBALS['TCA'], 'TCA', $references, $unreadableFiles);
        $this->assertNotSame([], $references, 'No label reference was found in the TCA at all.');

        $languageService = $this->get(LanguageServiceFactory::class)->create('default');
        $unresolved = [];
        foreach ($references as $reference => $origin) {
            if ($languageService->sL($reference) === '') {
                $unresolved[] = sprintf('%s (%s)', $reference, $origin);
            }
        }
        sort($unresolved);

        $this->assertSame([], $unreadableFiles, sprintf(
            "The TCA names FlexForm files that cannot be read:\n  %s",
            implode("\n  ", $unreadableFiles),
        ));
        $this->assertSame([], $unresolved, sprintf(
            "These label references resolve to an empty string, so the backend renders no label:\n  %s",
            implode("\n  ", $unresolved),
        ));
    }

    /**
     * @param array<string, string> $references Label reference => where it was found first
     * @param list<string> $unreadableFiles
     */
    private function collectLabelReferences(mixed $value, string $path, array &$references, array &$unreadableFiles): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $this->collectLabelReferences($child, $path . '/' . $key, $references, $unreadableFiles);
            }
            return;
        }
        if (!is_string($value)) {
            return;
        }
        if (str_starts_with($value, 'FILE:')) {
            $this->collectLabelReferencesOfFile(substr($value, 5), $path, $references, $unreadableFiles);
            return;
        }
        $this->collectLabelReferencesOfString($value, $path, $references, $unreadableFiles);
    }

    /**
     * @param array<string, string> $references
     * @param list<string> $unreadableFiles
     */
    private function collectLabelReferencesOfString(string $value, string $origin, array &$references, array &$unreadableFiles): void
    {
        if (preg_match_all('/LLL:EXT:[^\s;,"\'<>]+/', $value, $matches) > 0) {
            foreach ($matches[0] as $reference) {
                $references[$reference] ??= $origin;
            }
        }
        // A FlexForm data structure may name further files, a sheet for example.
        if (str_contains($value, '<') && preg_match_all('/FILE:(EXT:[^\s<>"\']+\.xml)/', $value, $matches) > 0) {
            foreach ($matches[1] as $file) {
                $this->collectLabelReferencesOfFile($file, $origin, $references, $unreadableFiles);
            }
        }
    }

    /**
     * @param array<string, string> $references
     * @param list<string> $unreadableFiles
     */
    private function collectLabelReferencesOfFile(string $file, string $origin, array &$references, array &$unreadableFiles): void
    {
        $absoluteFile = GeneralUtility::getFileAbsFileName($file);
        if ($absoluteFile === '' || !is_file($absoluteFile)) {
            $unreadableFiles[] = sprintf('%s (%s)', $file, $origin);
            return;
        }
        $this->collectLabelReferencesOfString((string)file_get_contents($absoluteFile), $file, $references, $unreadableFiles);
    }
}
