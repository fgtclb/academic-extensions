<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Upgrade;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Set\SetError;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Reports the stored configuration of an installation that no longer reaches
 * the academic extensions after an upgrade.
 *
 * Most of these do not fail loudly. TYPO3 skips a static template folder that
 * holds no TypoScript, an `@import` that matches no file and a
 * `tsconfig_includes` entry whose file is gone without a word, so an upgrade
 * that renames a folder leaves an installation that looks configured and is
 * not. Other checks cover the opposite mistake: configuration that arrives
 * twice, or that replaces a class the extension has since made `final`. Two
 * fail loudly, but only once the upgraded installation runs: an XCLASS of a
 * `final` class, and a site depending on a set TYPO3 cannot provide.
 *
 * The service only reads - no record, file or site configuration is written -
 * and behaves the same on TYPO3 v13 and v14.
 */
final readonly class ConfigurationChecker
{
    /**
     * The sets that were published before the configuration was cut per
     * component, and the set to depend on instead. They carry no payload of
     * their own; a site depending on one gets exactly what the aggregate
     * delivers.
     *
     * A constant rather than a marker in the set definitions: `config.yaml`
     * has no machine readable "this is an alias" key, and introducing a custom
     * one for two sets that go away in 4.0 buys nothing.
     */
    private const ALIAS_SETS = [
        'fgtclb/academic-persons-default' => 'fgtclb/academic-persons',
        'fgtclb/academic-study-plan-default' => 'fgtclb/academic-study-plan',
    ];

    /**
     * The sets a release removed, and what replaced them. A site that still
     * depends on one is reported like any other unavailable set; this adds the
     * way out to the message.
     */
    private const REMOVED_SETS = [
        'fgtclb/academic-programs-content-load' => '3.0 removed it: program pages render the content of their '
            . 'main column without it. Remove the dependency from the site configuration and from every set of '
            . 'the site package.',
    ];

    /**
     * The prefix of every set name of the academic extensions. `category_types`
     * ships no set.
     */
    private const SET_NAME_PREFIX = 'fgtclb/academic-';

    /**
     * The extension keys this check is about: everything of the academic
     * extension family. `category_types` is the one member whose key does not
     * carry the prefix.
     */
    private const EXTENSION_KEY_PREFIX = 'academic_';
    private const ADDITIONAL_EXTENSION_KEYS = ['category_types'];

    /**
     * The class name prefixes of the same family. A prefix rather than the
     * autoload configuration of the active packages, because an XCLASS of a
     * class that 3.0 *removed* is the interesting case, and that class is in
     * no active package any more.
     */
    private const CLASS_NAME_PREFIXES = ['FGTCLB\\Academic', 'FGTCLB\\CategoryTypes\\'];

    /**
     * What a static template folder has to hold for TYPO3 to read anything
     * from it, from `SysTemplateTreeBuilder::handleSingleIncludeStaticFile()`:
     * the include list, or a constants or setup file in one of three suffixes.
     * A folder holding none of them contributes nothing.
     */
    private const STATIC_TEMPLATE_FILE_NAMES = [
        'include_static_file.txt',
        'constants.typoscript',
        'constants.ts',
        'constants.txt',
        'setup.typoscript',
        'setup.ts',
        'setup.txt',
    ];

    /**
     * The file suffix `TreeFromLineStreamBuilder::processAtImport()` resolves an
     * `@import` against, which is the one thing that differs between the
     * TypoScript of a record and the page TSconfig of a page or a site.
     */
    private const SUFFIXES_TYPOSCRIPT = ['typoscript'];
    private const SUFFIXES_TSCONFIG = ['typoscript', 'tsconfig'];

    /**
     * How deep a page TSconfig import chain is followed.
     *
     * Correctness comes from the `seen` set, which bounds the recursion by the
     * number of distinct files. This is the second net, and it exists for what
     * happens when the first one breaks: without it, a cycle exhausts the
     * memory limit and takes the whole process down, so a defect here would
     * report itself as a fatal error in an unrelated place - in the backend
     * status report of an installation nobody can debug. With it, the same
     * defect produces too many findings, which is a defect that can be seen
     * and tested. Real chains are two or three files deep.
     */
    private const MAX_TSCONFIG_DEPTH = 25;

    public function __construct(
        private PackageManager $packageManager,
        private ConnectionPool $connectionPool,
        private SiteFinder $siteFinder,
        private FileNameValidator $fileNameValidator,
        private SetRegistry $setRegistry,
    ) {}

    /**
     * @return list<ConfigurationFinding>
     */
    public function check(): array
    {
        $sysTemplateRows = $this->sysTemplateRows();

        return [
            ...$this->checkStaticTemplates($sysTemplateRows),
            ...$this->checkRecordTypoScript($sysTemplateRows),
            ...$this->checkPageTsConfig(),
            ...$this->checkSites($sysTemplateRows),
            ...$this->checkXclasses(),
        ];
    }

    /**
     * @param list<array{uid: int, pid: int, title: string, clear: int, constants: string, config: string, include_static_file: string}> $sysTemplateRows
     * @return list<ConfigurationFinding>
     */
    private function checkStaticTemplates(array $sysTemplateRows): array
    {
        $findings = [];
        foreach ($sysTemplateRows as $row) {
            foreach (GeneralUtility::trimExplode(',', $row['include_static_file'], true) as $value) {
                $extensionKey = $this->academicExtensionKeyOf($value);
                if ($extensionKey === null) {
                    continue;
                }
                $reason = $this->staticTemplateVerdict($extensionKey, $value);
                if ($reason === null) {
                    continue;
                }
                $findings[] = new ConfigurationFinding(
                    ConfigurationFindingKind::StaticTemplate,
                    ContextualFeedbackSeverity::WARNING,
                    sprintf('sys_template:%d', $row['uid']),
                    sprintf(
                        'The TypoScript record "%s" on page %d includes the static template "%s": %s '
                        . 'Select the static template of the installed version instead, or remove the value.',
                        $row['title'],
                        $row['pid'],
                        $value,
                        $reason,
                    ),
                );
            }
        }

        return $findings;
    }

    /**
     * What is wrong with a stored static template value, or `null` when it
     * still delivers.
     *
     * The three answers are the three things core does with such a value in
     * `SysTemplateTreeBuilder::handleSingleIncludeStaticFile()`: it throws when
     * the value names no folder below the extension, it returns when the
     * extension is not loaded, and it adds nothing when the folder holds none
     * of the files it reads. Only the last two are silent, which is why the
     * first one gets a message of its own.
     */
    private function staticTemplateVerdict(string $extensionKey, string $value): ?string
    {
        // The path is read as core reads it: `trimExplode('/', ..., true, 2)`
        // drops the empty segment the leading slash produces and trims what is
        // left, so whitespace between the extension key and the folder is not
        // part of the path. The order matters - trimming before the slash is
        // gone leaves the space that follows it.
        $relativePath = trim(trim(substr($value, strlen('EXT:' . $extensionKey)), '/'));
        if ($relativePath === '') {
            return 'it names no folder below the extension. TYPO3 does not skip such a value - it throws '
                . 'the runtime exception 1651138603 while it builds the TypoScript of every page on and '
                . 'below the one holding this record.';
        }
        if (!$this->packageManager->isPackageActive($extensionKey)) {
            return sprintf(
                'the extension "%s" is not installed, so TYPO3 skips the value without a message.',
                $extensionKey,
            );
        }
        $folder = rtrim($this->packageManager->getPackage($extensionKey)->getPackagePath(), '/')
            . '/' . $relativePath . '/';
        foreach (self::STATIC_TEMPLATE_FILE_NAMES as $fileName) {
            if (is_file($folder . $fileName)) {
                return null;
            }
        }

        return 'it holds no TypoScript in the installed version, so TYPO3 skips the value without a message.';
    }

    /**
     * Every page TSconfig an installation stores, and every reference to an
     * academic file in it that TYPO3 does not read.
     *
     * Three places hold page TSconfig: the `TSconfig` field of a page, the
     * files selected in its `tsconfig_includes` field, and the
     * `page.tsconfig` file next to a site configuration - the last one on
     * TYPO3 v13 and v14 alike (`TsConfigTreeBuilder::getSitePageTsConfigTree()`).
     *
     * `Site::getTSconfig()` and `SiteTSconfig` are annotated `@internal` on
     * both versions. There is no public API for the page TSconfig of a site,
     * so this is a deliberate use of internal API and belongs on the same
     * TYPO3 v15 re-check list as the composer manifest reader of the command.
     *
     * @return list<ConfigurationFinding>
     */
    private function checkPageTsConfig(): array
    {
        $findings = [];
        foreach ($this->pageRows() as $row) {
            $subject = sprintf('pages:%d', $row['uid']);
            $origin = sprintf('the page %d ("%s")', $row['uid'], $row['title']);
            $seen = [];
            foreach (GeneralUtility::trimExplode(',', $row['tsconfig_includes'], true) as $value) {
                $findings = [
                    ...$findings,
                    ...$this->checkTsConfigInclude($subject, ucfirst($origin), $origin, $value, $seen),
                ];
            }
            $findings = [
                ...$findings,
                ...$this->checkTsConfigContent($subject, ucfirst($origin), $origin, $row['TSconfig'], $seen),
            ];
        }

        $sites = $this->siteFinder->getAllSites();
        ksort($sites, SORT_STRING);
        foreach ($sites as $site) {
            $seen = [];
            $findings = [
                ...$findings,
                ...$this->checkTsConfigContent(
                    sprintf('site:%s', $site->getIdentifier()),
                    sprintf('The file "config/sites/%s/page.tsconfig"', $site->getIdentifier()),
                    sprintf('the site "%s"', $site->getIdentifier()),
                    $site->getTSconfig()?->pageTSconfig ?? '',
                    $seen,
                ),
            ];
        }

        return $findings;
    }

    /**
     * One value of a page's `tsconfig_includes` field.
     *
     * The conditions are the ones of `TsConfigTreeBuilder::getRootlinePageTsConfigTree()`:
     * an `EXT:` path, an active extension, and a file that exists below that
     * extension. Anything else is dropped there without a message.
     *
     * A file that does exist is read, so that an unresolved academic reference
     * inside it is reported with the page that leads TYPO3 to it - which is
     * what TYPO3 does with the content it finds there.
     *
     * @param array<string, true> $seen
     * @return list<ConfigurationFinding>
     */
    private function checkTsConfigInclude(
        string $subject,
        string $location,
        string $origin,
        string $value,
        array &$seen,
    ): array {
        // A selected file is *not* resolved the way an "@import" is: core does
        // one exact file lookup for it and nothing else - no folder, no
        // wildcard, no appended suffix, and no suffix requirement at all. A
        // file of a project or of another extension is read all the same, and
        // is followed here for the same reason: the case this exists for is a
        // project file that resolves and imports an academic path a release
        // renamed.
        $path = $this->selectedTsConfigPath($value);
        $extensionKey = $this->academicExtensionKeyOf($value);
        if ($extensionKey === null) {
            return $path !== null && is_file($path)
                ? $this->followTsConfigFile($subject, $origin, $path, $seen, 0)
                : [];
        }
        if (!$this->packageManager->isPackageActive($extensionKey)) {
            return [$this->tsConfigImportFinding($subject, sprintf(
                '%s selects the page TSconfig file "%s", but the extension "%s" is not installed.',
                $location,
                $value,
                $extensionKey,
            ))];
        }
        if ($path !== null && is_file($path)) {
            return $this->followTsConfigFile($subject, $origin, $path, $seen, 0);
        }
        if ($path !== null && is_dir($path)) {
            // Core guards this with `file_exists()`, which a directory passes,
            // and then calls `file_get_contents()` on it: nothing is read and
            // the frontend raises a warning. Selecting a folder is never what
            // the integrator meant - unlike an "@import", a selected value is
            // one file and nothing else.
            return [$this->tsConfigImportFinding($subject, sprintf(
                '%s selects "%s", which is a folder. A selected page TSconfig value names one file and '
                . 'is not expanded the way an "@import" is, so TYPO3 reads nothing from it and raises a '
                . 'warning while doing so. Select the file itself.',
                $location,
                $value,
            ))];
        }

        return [$this->tsConfigImportFinding($subject, sprintf(
            '%s selects the page TSconfig file "%s", which the installed version of "%s" does not ship.',
            $location,
            $value,
            $extensionKey,
        ))];
    }

    /**
     * The academic references of one page TSconfig string, and of every file
     * its references lead to.
     *
     * @param array<string, true> $seen Absolute file names already read, so that two files importing each other terminate.
     * @return list<ConfigurationFinding>
     */
    private function checkTsConfigContent(
        string $subject,
        string $location,
        string $origin,
        string $tsConfig,
        array &$seen,
        int $depth = 0,
    ): array {
        if (trim($tsConfig) === '') {
            return [];
        }
        $findings = [];
        foreach ($this->tsConfigReferences($tsConfig) as $reference) {
            $extensionKey = $this->academicExtensionKeyOf($reference['path']);
            if ($extensionKey === null) {
                if (!$reference['legacySyntax']) {
                    $findings = [
                        ...$findings,
                        ...$this->followTsConfigFiles($subject, $origin, $reference['path'], $seen, $depth),
                    ];
                }
                continue;
            }
            if ($reference['legacySyntax']) {
                // Not a question of whether the file is there: TYPO3 v14 dropped
                // the syntax itself (Breaking-105377), so the line is read by
                // neither of the two supported versions any more - v13 reads it
                // and raises a deprecation, v14 drops it silently.
                $findings[] = new ConfigurationFinding(
                    ConfigurationFindingKind::TsConfigSyntax,
                    ContextualFeedbackSeverity::WARNING,
                    $subject,
                    sprintf(
                        '%s includes "%s" with the "<INCLUDE_TYPOSCRIPT:" syntax, which TYPO3 v13 '
                        . 'deprecated and TYPO3 v14 removed - there it is ignored without a message. '
                        . 'Write it as "@import \'%s\'" instead.%s',
                        $location,
                        $reference['path'],
                        $reference['path'],
                        $reference['directory']
                            ? ' Note that "@import" of a folder reads its "*.tsconfig" files and does '
                                . 'not descend into subfolders, which "DIR:" did.'
                            : '',
                    ),
                );
                continue;
            }
            if ($this->atImportFilesOfType($reference['path'], self::SUFFIXES_TSCONFIG) !== []) {
                $findings = [
                    ...$findings,
                    ...$this->followTsConfigFiles($subject, $origin, $reference['path'], $seen, $depth),
                ];
                continue;
            }
            $findings[] = $this->tsConfigImportFinding($subject, $this->deadImportMessage(
                $location,
                $reference['path'],
                $extensionKey,
                self::SUFFIXES_TSCONFIG,
            ));
        }

        return $findings;
    }

    /**
     * Reads every file a page TSconfig reference resolves to and checks its
     * content, so an unresolved academic reference one file further in is
     * reported with the page or site that leads TYPO3 to it.
     *
     * The `seen` set is keyed by the absolute file name and is what makes two
     * files importing each other terminate. Core has no such guard; it is
     * protected by the file system rather than by the code, and a check must
     * not be. {@see MAX_TSCONFIG_DEPTH} is the second net behind it.
     *
     * @param array<string, true> $seen
     * @return list<ConfigurationFinding>
     */
    private function followTsConfigFiles(
        string $subject,
        string $origin,
        string $value,
        array &$seen,
        int $depth,
    ): array {
        $findings = [];
        foreach ($this->atImportFilesOfType($value, self::SUFFIXES_TSCONFIG) as $fileName) {
            $findings = [...$findings, ...$this->followTsConfigFile($subject, $origin, $fileName, $seen, $depth)];
        }

        return $findings;
    }

    /**
     * Reads one page TSconfig file and checks its content.
     *
     * The location names the file rather than the expression that led to it:
     * a folder or wildcard import resolves to several files, and "the file
     * EXT:my_site/Configuration/TSconfig/" would name a folder and leave the
     * integrator to find which of its files carries the line.
     *
     * @param array<string, true> $seen
     * @return list<ConfigurationFinding>
     */
    private function followTsConfigFile(
        string $subject,
        string $origin,
        string $fileName,
        array &$seen,
        int $depth,
    ): array {
        if ($depth >= self::MAX_TSCONFIG_DEPTH || isset($seen[$fileName])) {
            return [];
        }
        $seen[$fileName] = true;
        $content = @file_get_contents($fileName);
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        return $this->checkTsConfigContent(
            $subject,
            sprintf('The file "%s", reached from %s,', $this->displayPath($fileName), $origin),
            $origin,
            $content,
            $seen,
            $depth + 1,
        );
    }

    /**
     * The path a `tsconfig_includes` value selects, or `null` when TYPO3 does
     * not even look at it.
     *
     * This is `TsConfigTreeBuilder::getContentOfTsconfigFile()` on TYPO3 v14
     * and the same logic inlined in `getRootlinePageTsConfigTree()` on v13: an
     * `EXT:` path, an active extension, and a canonical path that stays inside
     * that extension. Deliberately *not* the four shapes of an `@import` - a
     * selected folder, a name without its suffix and a wildcard all read
     * nothing there, while a file of any suffix reads fine.
     *
     * Whether the path is a file is left to the caller, because core's
     * `file_exists()` accepts a folder and then reads nothing from it - which
     * is a finding of its own rather than a file to follow.
     */
    private function selectedTsConfigPath(string $value): ?string
    {
        if (!PathUtility::isExtensionPath($value)) {
            return null;
        }
        $parts = explode('/', substr($value, strlen('EXT:')), 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '' || !$this->packageManager->isPackageActive($parts[0])) {
            return null;
        }
        $extensionPath = rtrim($this->packageManager->getPackage($parts[0])->getPackagePath(), '/') . '/';
        $path = PathUtility::getCanonicalPath($extensionPath . $parts[1]);

        return str_starts_with($path, $extensionPath) && file_exists($path) ? $path : null;
    }

    /**
     * An absolute file name as it is worth printing: as an `EXT:` path when the
     * file belongs to an active package, and relative to the project root
     * otherwise. Both are what an integrator searches for; the absolute path of
     * a package is not.
     */
    private function displayPath(string $fileName): string
    {
        $match = null;
        foreach ($this->packageManager->getActivePackages() as $package) {
            $packagePath = rtrim($package->getPackagePath(), '/') . '/';
            // The longest match, not the first: a package inside another
            // package's folder would otherwise be named after its host.
            if (str_starts_with($fileName, $packagePath)
                && ($match === null || strlen($packagePath) > strlen($match[1]))
            ) {
                $match = [$package->getPackageKey(), $packagePath];
            }
        }
        if ($match !== null) {
            return sprintf('EXT:%s/%s', $match[0], substr($fileName, strlen($match[1])));
        }
        $projectPath = rtrim(Environment::getProjectPath(), '/') . '/';

        return str_starts_with($fileName, $projectPath)
            ? substr($fileName, strlen($projectPath))
            : $fileName;
    }

    /**
     * The academic references in the Constants and Setup fields of the
     * TypoScript records.
     *
     * Core tokenizes both fields in `SysTemplateTreeBuilder` and hands the
     * stream to the same `TreeFromLineStreamBuilder` that reads page TSconfig,
     * so an `@import` matching no file is dropped there exactly as silently -
     * only against the `.typoscript` suffix. This is the case the 2.4 entry
     * `Breaking-SiteSetsAndStaticTemplatesRestructured.rst` of academic_persons
     * describes: "A site package that imported one of the removed files by path
     * fails to resolve it."
     *
     * Only the fields themselves are read, not the files they import. The first
     * level is where a renamed path bites, and reading the whole TypoScript tree
     * of an installation on every status report render is not worth the second.
     *
     * @param list<array{uid: int, pid: int, title: string, clear: int, constants: string, config: string, include_static_file: string}> $sysTemplateRows
     * @return list<ConfigurationFinding>
     */
    private function checkRecordTypoScript(array $sysTemplateRows): array
    {
        $findings = [];
        foreach ($sysTemplateRows as $row) {
            foreach (['constants' => 'Constants', 'config' => 'Setup'] as $column => $label) {
                $location = sprintf('The TypoScript record %d ("%s"), field "%s",', $row['uid'], $row['title'], $label);
                foreach ($this->tsConfigReferences($row[$column]) as $reference) {
                    $extensionKey = $this->academicExtensionKeyOf($reference['path']);
                    if ($extensionKey === null) {
                        continue;
                    }
                    if ($reference['legacySyntax']) {
                        $findings[] = new ConfigurationFinding(
                            ConfigurationFindingKind::TypoScriptSyntax,
                            ContextualFeedbackSeverity::WARNING,
                            sprintf('sys_template:%d', $row['uid']),
                            sprintf(
                                '%s includes "%s" with the "<INCLUDE_TYPOSCRIPT:" syntax, which TYPO3 v13 '
                                . 'deprecated and TYPO3 v14 removed - there it is ignored without a message. '
                                . 'Write it as "@import \'%s\'" instead.%s',
                                $location,
                                $reference['path'],
                                $reference['path'],
                                $reference['directory']
                                    ? ' Note that "@import" of a folder reads its "*.typoscript" files and '
                                        . 'does not descend into subfolders, which "DIR:" did.'
                                    : '',
                            ),
                        );
                        continue;
                    }
                    if ($this->atImportFilesOfType($reference['path'], self::SUFFIXES_TYPOSCRIPT) !== []) {
                        continue;
                    }
                    $findings[] = new ConfigurationFinding(
                        ConfigurationFindingKind::TypoScriptImport,
                        ContextualFeedbackSeverity::WARNING,
                        sprintf('sys_template:%d', $row['uid']),
                        $this->deadImportMessage(
                            $location,
                            $reference['path'],
                            $extensionKey,
                            self::SUFFIXES_TYPOSCRIPT,
                            'Correct the path, or depend on the site set of the extension instead of '
                                . 'importing its files.',
                        ),
                    );
                }
            }
        }

        return $findings;
    }

    /**
     * Why an `@import` of an academic file reads nothing, in the words the
     * integrator needs.
     *
     * Three causes, and telling them apart is the difference between a report
     * that is believed and one that is not: the extension is gone, the path
     * matches nothing, or it matches a file that is plainly there and whose
     * *name* TYPO3 refuses through its `fileDenyPattern` - for which core
     * contributes nothing in every one of the four `@import` shapes.
     *
     * @param list<string> $fileSuffixes
     */
    private function deadImportMessage(
        string $location,
        string $path,
        string $extensionKey,
        array $fileSuffixes,
        string $advice = '',
    ): string {
        if (!$this->packageManager->isPackageActive($extensionKey)) {
            return sprintf(
                '%s imports "%s", but the extension "%s" is not installed. TYPO3 skips the import '
                . 'without a message.',
                $location,
                $path,
                $extensionKey,
            );
        }
        $refused = $this->atImportFilesRefusedByName($path, $fileSuffixes);
        if ($refused !== []) {
            return sprintf(
                '%s imports "%s", which matches the file "%s" - a name TYPO3 refuses through its '
                . '"fileDenyPattern". It contributes nothing for such a file, so the import is dead '
                . 'although the file is there. Rename it.',
                $location,
                $path,
                $this->displayPath($refused[0]),
            );
        }

        return rtrim(sprintf(
            '%s imports "%s", which matches no file of the installed version. TYPO3 skips the import '
            . 'without a message. %s',
            $location,
            $path,
            $advice,
        ));
    }

    private function tsConfigImportFinding(string $subject, string $message): ConfigurationFinding
    {
        return new ConfigurationFinding(
            ConfigurationFindingKind::TsConfigImport,
            ContextualFeedbackSeverity::WARNING,
            $subject,
            $message,
        );
    }

    /**
     * The file references of a page TSconfig string, in the order they appear.
     *
     * The two syntaxes are read the way `LossyTokenizer` reads them: `@import`
     * takes a quoted value, `<INCLUDE_TYPOSCRIPT:` an attribute list up to the
     * closing angle bracket. Running the tokenizer itself is not an option -
     * it answers with a line stream of the *whole* TSconfig, and resolving
     * that means building the include tree, which is the very step that drops
     * a missing file silently.
     *
     * @return list<array{path: string, legacySyntax: bool, directory: bool}>
     */
    private function tsConfigReferences(string $tsConfig): array
    {
        $references = [];
        $inMultiLineComment = false;
        foreach (explode("\n", $tsConfig) as $line) {
            $line = trim(rtrim($line, "\r"));
            if ($inMultiLineComment) {
                // `LossyTokenizer::ignoreUntilEndOfMultilineComment()` skips every
                // line up to and including the one holding the comment end.
                $inMultiLineComment = !str_contains($line, '*/');
                continue;
            }
            // Two deliberate differences from the tokenizer, in opposite
            // directions. A line that is exactly "/*/" leaves the comment open
            // here and closes it there, so this reads less than TYPO3 does. A
            // comment opened *after* something the tokenizer has already parsed
            // on the line - "@import 'x' /*", "foo >" followed by "/*", "[END]
            // /*" - puts it into a skip this check does not enter, so there it
            // reads more. A comment opened behind an assignment is no comment
            // for the tokenizer at all: parseOperatorAssignment() takes the rest
            // of the line as the value, which is what its own @todo means by
            // "foo = bar /* not a comment */". The common shape therefore
            // agrees, and reaching either difference needs a block comment
            // opened on the same line as an import, an unset or a condition.
            if (str_starts_with($line, '/*')) {
                $inMultiLineComment = !str_contains(substr($line, 2), '*/');
                continue;
            }
            if (str_starts_with($line, '@import')) {
                $value = trim(substr($line, 7));
                $quote = substr($value, 0, 1);
                if ($quote !== '\'' && $quote !== '"') {
                    continue;
                }
                $end = strpos($value, $quote, 1);
                $path = $end === false ? substr($value, 1) : substr($value, 1, $end - 1);
                if ($path !== '') {
                    $references[] = ['path' => $path, 'legacySyntax' => false, 'directory' => false];
                }
                continue;
            }
            if (!str_starts_with($line, '<INCLUDE_TYPOSCRIPT:')) {
                continue;
            }
            // Deliberately looser than `processIncludeTyposcript()`, which
            // reads the attributes case sensitively: a "SOURCE=" or a "file:"
            // was never read by TYPO3 either, so reporting it is the answer -
            // it is dead configuration whichever way one counts.
            $body = $this->includeTypoScriptBody(substr($line, strlen('<INCLUDE_TYPOSCRIPT:')));
            if (preg_match('/source\s*=\s*"(FILE|DIR):([^"]*)"/i', $body, $matches) !== 1) {
                continue;
            }
            $path = trim($matches[2]);
            if ($path !== '') {
                $references[] = [
                    'path' => $path,
                    'legacySyntax' => true,
                    'directory' => strtoupper($matches[1]) === 'DIR',
                ];
            }
        }

        return $references;
    }

    /**
     * Everything of a `<INCLUDE_TYPOSCRIPT:` line up to the closing angle
     * bracket, the way `LossyTokenizer::parseImportOld()` finds it: a `>`
     * inside a double quoted attribute value does not close the tag. Cutting
     * at the first `>` instead loses the `source` of an include that carries a
     * `condition="[... > ...]"` attribute before it.
     */
    private function includeTypoScriptBody(string $body): string
    {
        $isWithinDoubleTick = false;
        $previousCharWasQuote = false;
        $length = strlen($body);
        for ($position = 0; $position < $length; $position++) {
            $character = $body[$position];
            if ($character === '"' && !$previousCharWasQuote) {
                $isWithinDoubleTick = !$isWithinDoubleTick;
            }
            $previousCharWasQuote = $character === '\\';
            if ($character === '>' && !$isWithinDoubleTick) {
                return substr($body, 0, $position);
            }
        }

        return $body;
    }

    /**
     * The files an `@import` reads, in the order it reads them - empty when it
     * reads none, which is what "the import is dead" means.
     *
     * The four shapes are the ones `TreeFromLineStreamBuilder::processAtImport()`
     * handles for a string that is not itself a file: an exact file, a folder,
     * a file name without its suffix, and one wildcard in the file name.
     *
     * The relative lookup of that method is **not** reproduced. For a string
     * out of the database that costs nothing - core returns early there too,
     * because the node has no path - but for the content of a file this check
     * follows it does: core sets the path of such a node, so a relative import
     * inside a followed file resolves there and is missed here. A relative
     * path is never an academic one, so this can only under-report, and it is
     * named in both "does not check" lists.
     *
     * @return list<string> Absolute file names.
     */
    private function atImportFiles(string $value, string $fileSuffix, bool $applyDenyPattern = true): array
    {
        $absolutePath = rtrim(GeneralUtility::getFileAbsFileName($value), '/');
        if ($absolutePath === '') {
            return [];
        }
        if (str_ends_with($absolutePath, '.' . $fileSuffix) && is_file($absolutePath)) {
            return !$applyDenyPattern || $this->fileNameValidator->isValid($absolutePath) ? [$absolutePath] : [];
        }
        if (is_dir($absolutePath)) {
            return $this->filesMatching($absolutePath . '/', '', '.' . $fileSuffix, $applyDenyPattern);
        }
        if (is_file($absolutePath . '.' . $fileSuffix)) {
            $withSuffix = $absolutePath . '.' . $fileSuffix;

            return !$applyDenyPattern || $this->fileNameValidator->isValid($withSuffix) ? [$withSuffix] : [];
        }
        if (!str_contains($absolutePath, '*')) {
            return [];
        }
        $folder = rtrim(dirname($absolutePath), '/') . '/';
        $filePattern = basename($absolutePath);
        if (!is_dir($folder) || substr_count($filePattern, '*') !== 1) {
            return [];
        }
        if (str_ends_with($filePattern, $fileSuffix)) {
            $filePattern = rtrim(substr($filePattern, 0, -strlen($fileSuffix)), '.');
        }
        $filePattern .= '.' . $fileSuffix;
        $wildcardPosition = (int)strpos($filePattern, '*');

        return $this->filesMatching(
            $folder,
            substr($filePattern, 0, $wildcardPosition),
            substr($filePattern, $wildcardPosition + 1),
            $applyDenyPattern,
        );
    }

    /**
     * The files an `@import` of one *type* reads: core maps the type to a list
     * of allowed suffixes and calls `processAtImport()` once per suffix
     * (`TreeFromLineStreamBuilder::$atImportTypeToSuffixMap`). TypoScript
     * allows `.typoscript`; **page TSconfig allows `.typoscript` and
     * `.tsconfig`**, in that order.
     *
     * Each pass yields only files ending in its own suffix - the exact and
     * append shapes require it, the folder shape filters by it, and the
     * wildcard shape normalises the pattern to it - so the two sets are
     * disjoint and the array keys below are a formality rather than a dedupe
     * of anything reachable.
     *
     * @param list<string> $fileSuffixes
     * @return list<string> Absolute file names.
     */
    private function atImportFilesOfType(string $value, array $fileSuffixes, bool $applyDenyPattern = true): array
    {
        $files = [];
        foreach ($fileSuffixes as $fileSuffix) {
            foreach ($this->atImportFiles($value, $fileSuffix, $applyDenyPattern) as $fileName) {
                $files[$fileName] = true;
            }
        }

        return array_keys($files);
    }

    /**
     * The files an `@import` would have read if TYPO3's `fileDenyPattern` did
     * not refuse their names - empty unless that is what makes the import
     * dead.
     *
     * Worth the second pass on the finding path only: the file is there, an
     * integrator can see it, and "matches no file of the installed version"
     * is not a statement they would believe about a file they are looking at.
     *
     * @param list<string> $fileSuffixes
     * @return list<string> Absolute file names.
     */
    private function atImportFilesRefusedByName(string $value, array $fileSuffixes): array
    {
        return array_values(array_diff(
            $this->atImportFilesOfType($value, $fileSuffixes, false),
            $this->atImportFilesOfType($value, $fileSuffixes),
        ));
    }

    /**
     * @return list<string>
     */
    private function filesMatching(string $folder, string $prefix, string $suffix, bool $applyDenyPattern = true): array
    {
        $files = [];
        foreach ((array)(scandir($folder) ?: []) as $entry) {
            $entry = (string)$entry;
            if ($entry === '.' || $entry === '..' || is_dir($folder . $entry)) {
                continue;
            }
            if (!str_starts_with($entry, $prefix) || !str_ends_with($entry, $suffix)) {
                continue;
            }
            // Core guards every file it reads with the deny pattern - all four
            // shapes of processAtImport(), not only this one. A name it refuses
            // ("x.php.tsconfig") makes the import contribute nothing, so the
            // import is dead and this check has to say so rather than read the
            // file and call it resolved.
            if ($applyDenyPattern && !$this->fileNameValidator->isValid($folder . $entry)) {
                continue;
            }
            $files[] = $folder . $entry;
        }
        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * The checks a site configuration carries: a dependency on an alias set, on
     * a set TYPO3 cannot provide, and one extension delivered through a set and
     * through a static template at the same time.
     *
     * A dependency TYPO3 cannot provide is the one check here that core does
     * not keep quiet about - `SiteConfiguration::determineInvalidSets()` marks
     * the name, and `SiteResolver` answers every frontend request of the site
     * with HTTP 500. It is reported anyway, because the command runs before an
     * upgraded installation goes live and the site does not.
     *
     * Only the *declared* dependencies of a site are looked at, because that is
     * what `Site::getSets()` answers - a set reached through another one is not
     * in the list, and is a deliberate choice of whoever wrote the set that
     * pulls it in.
     *
     * @param list<array{uid: int, pid: int, title: string, clear: int, constants: string, config: string, include_static_file: string}> $sysTemplateRows
     * @return list<ConfigurationFinding>
     */
    private function checkSites(array $sysTemplateRows): array
    {
        $extensionKeyBySetName = $this->academicSetNames();
        $sites = $this->siteFinder->getAllSites();
        ksort($sites, SORT_STRING);

        $findings = [];
        foreach ($sites as $site) {
            $subject = sprintf('site:%s', $site->getIdentifier());
            $extensionKeysWithASet = [];
            foreach ($site->getSets() as $setName) {
                // An alias whose extension is not installed is an unavailable set, and the
                // notice would call it a set that still delivers.
                $unavailableSet = $this->unavailableAcademicSet($setName);
                if ($unavailableSet !== null) {
                    $findings[] = new ConfigurationFinding(
                        ConfigurationFindingKind::UnavailableSet,
                        ContextualFeedbackSeverity::ERROR,
                        $subject,
                        rtrim(sprintf(
                            'The site "%s" depends on "%s"%s. TYPO3 cannot provide "%s": no active extension '
                            . 'ships a valid set of that name. TYPO3 answers every page of the site with HTTP 500 '
                            . '("depends on unavailable sets") until the dependency is removed or the set is '
                            . 'available again. %s',
                            $site->getIdentifier(),
                            $setName,
                            $unavailableSet === $setName
                                ? ''
                                : sprintf(', which needs "%s" - directly or through another set', $unavailableSet),
                            $unavailableSet,
                            self::REMOVED_SETS[$unavailableSet] ?? '',
                        )),
                    );
                } elseif (isset(self::ALIAS_SETS[$setName])) {
                    $findings[] = new ConfigurationFinding(
                        ConfigurationFindingKind::AliasSet,
                        ContextualFeedbackSeverity::NOTICE,
                        $subject,
                        sprintf(
                            'The site "%s" depends on "%s", which is an alias without payload kept for '
                            . 'site configurations written before 3.0. Depend on "%s" instead, or on the '
                            . 'component sets the site actually needs.',
                            $site->getIdentifier(),
                            $setName,
                            self::ALIAS_SETS[$setName],
                        ),
                    );
                }
                $extensionKey = $extensionKeyBySetName[$setName] ?? null;
                if ($extensionKey !== null) {
                    $extensionKeysWithASet[$extensionKey] = true;
                }
            }
            $rootPageId = $site->getRootPageId();
            $findings = [...$findings, ...$this->clearedSetBranches($site, $rootPageId, $sysTemplateRows)];
            if ($extensionKeysWithASet === []) {
                continue;
            }
            $extensionKeysWithAStaticTemplate = [];
            foreach ($sysTemplateRows as $row) {
                if ($row['pid'] !== $rootPageId) {
                    continue;
                }
                foreach (GeneralUtility::trimExplode(',', $row['include_static_file'], true) as $value) {
                    $extensionKey = $this->academicExtensionKeyOf($value);
                    if ($extensionKey !== null) {
                        $extensionKeysWithAStaticTemplate[$extensionKey] = true;
                    }
                }
            }
            foreach (array_keys($extensionKeysWithASet) as $extensionKey) {
                if (!isset($extensionKeysWithAStaticTemplate[$extensionKey])) {
                    continue;
                }
                $findings[] = new ConfigurationFinding(
                    ConfigurationFindingKind::SetAndStaticTemplate,
                    ContextualFeedbackSeverity::WARNING,
                    $subject,
                    sprintf(
                        'The site "%s" delivers "%s" through a site set and through a static template of a '
                        . 'TypoScript record on its root page %d. Both are parsed, and the set is parsed '
                        . 'first, so the record can reset a constant the site settings had set. Use one '
                        . 'mechanism per site - see the "Configuration" chapter of the extension manual.',
                        $site->getIdentifier(),
                        $extensionKey,
                        $rootPageId,
                    ),
                );
            }
        }

        return $findings;
    }

    /**
     * The academic set that keeps a declared dependency of a site from being
     * available, or `null` when there is none.
     *
     * The answer is the set that is missing: the declared set itself, or the
     * set it depends on, directly or further down, when that is what makes it
     * invalid. `SetRegistry::checkMissingDependencies()` records the path below
     * the declared set as `b[c[missing]]`, so the missing set is the innermost
     * name. It is reported when either end is academic - an academic set that
     * misses a set of another vendor or is invalid for any other reason, or a
     * site package set that misses an academic one. Only the two ends are
     * looked at: a set of another vendor that reaches a missing set of another
     * vendor through an academic set is not reported, which is theoretical
     * while no academic set depends on a set of another vendor. The backend
     * module "Sites" ("Sites > Setup" on TYPO3 v14) lists every invalid set.
     */
    private function unavailableAcademicSet(string $setName): ?string
    {
        if ($this->setRegistry->hasSet($setName)) {
            return null;
        }
        $invalidSet = $this->setRegistry->getInvalidSets()[$setName] ?? null;
        $missingSet = $setName;
        if ($invalidSet !== null && $invalidSet['error'] === SetError::missingDependency) {
            $chain = rtrim($invalidSet['context'], ']');
            $position = strrpos($chain, '[');
            $missingSet = $position === false ? $chain : substr($chain, $position + 1);
        }

        return str_starts_with($setName, self::SET_NAME_PREFIX) || str_starts_with($missingSet, self::SET_NAME_PREFIX)
            ? $missingSet
            : null;
    }

    /**
     * The TypoScript records on a site's root page that discard what its site
     * sets deliver.
     *
     * `SysTemplateTreeBuilder` adds the site include to the root node before
     * the `sys_template` rows, marks a row whose bit for the branch is set as
     * "clear" (`clear & 1` for constants, `clear & 2` for setup), and
     * `IncludeTreeAstBuilderVisitor::visitBeforeChildren()` replaces the whole
     * AST with a fresh root node for such a node. So the record wipes the set
     * contribution to that branch, and the failure looks like "the extension
     * ships no TypoScript" rather than like a template record problem.
     *
     * The condition is `Site::getSets()`, not `Site::isTypoScriptRoot()`: the
     * latter also answers true for a site that only carries TypoScript of its
     * own, which is none of this check's business, and it is `@internal` on
     * both core versions while `getSets()` is not.
     *
     * @param list<array{uid: int, pid: int, title: string, clear: int, constants: string, config: string, include_static_file: string}> $sysTemplateRows
     * @return list<ConfigurationFinding>
     */
    private function clearedSetBranches(Site $site, int $rootPageId, array $sysTemplateRows): array
    {
        if ($site->getSets() === []) {
            return [];
        }
        $findings = [];
        foreach ($sysTemplateRows as $row) {
            if ($row['pid'] !== $rootPageId) {
                continue;
            }
            $branches = [];
            if (($row['clear'] & 1) !== 0) {
                $branches[] = 'Constants';
            }
            if (($row['clear'] & 2) !== 0) {
                $branches[] = 'Setup';
            }
            if ($branches === []) {
                continue;
            }
            $findings[] = new ConfigurationFinding(
                ConfigurationFindingKind::SetBranchCleared,
                ContextualFeedbackSeverity::WARNING,
                sprintf('site:%s', $site->getIdentifier()),
                sprintf(
                    'The TypoScript record %d ("%s") on the root page %d of the site "%s" clears %s, and '
                    . 'the site delivers TypoScript through site sets. TYPO3 reads the sets before the '
                    . 'record, so the flag discards everything they contributed to %s - which looks like '
                    . 'an extension that ships no TypoScript rather than like a template record. The '
                    . 'backend button "Create a root TypoScript record" writes both flags. Clear the flag, '
                    . 'or carry the configuration in the record itself.',
                    $row['uid'],
                    $row['title'],
                    $rootPageId,
                    $site->getIdentifier(),
                    implode(' and ', $branches),
                    count($branches) === 1 ? 'that branch' : 'both branches',
                ),
            );
        }

        return $findings;
    }

    /**
     * The set names of the active academic extensions, mapped to the extension
     * key that ships them.
     *
     * Read from the `config.yaml` files the core reads, because the names do
     * not map to an extension key mechanically: `fgtclb/academic-contacts4pages`
     * ships from the directory `academic-contact4pages` with the extension key
     * `academic_contacts4pages`.
     *
     * @return array<string, string>
     */
    private function academicSetNames(): array
    {
        $extensionKeyBySetName = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $extensionKey = $package->getPackageKey();
            if (!$this->isAcademicExtensionKey($extensionKey)) {
                continue;
            }
            $setFiles = glob(rtrim($package->getPackagePath(), '/') . '/Configuration/Sets/*/config.yaml') ?: [];
            foreach ($setFiles as $setFile) {
                $configuration = Yaml::parseFile($setFile);
                $name = is_array($configuration) ? ($configuration['name'] ?? null) : null;
                if (is_string($name) && $name !== '') {
                    $extensionKeyBySetName[$name] = $extensionKey;
                }
            }
        }

        return $extensionKeyBySetName;
    }

    /**
     * The XCLASS registrations replacing a class of an academic extension.
     *
     * Only the replaced class is reflected, never the replacement: loading a
     * class that extends one the upgrade removed is a fatal error, and a check
     * that reports the problem by dying of it helps nobody.
     *
     * @return list<ConfigurationFinding>
     */
    private function checkXclasses(): array
    {
        /** @var array<string, mixed> $objects */
        $objects = (array)($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] ?? []);
        ksort($objects, SORT_STRING);

        $findings = [];
        foreach ($objects as $className => $registration) {
            $className = (string)$className;
            if (!$this->isAcademicClassName($className)) {
                continue;
            }
            $xclassName = is_array($registration) ? ($registration['className'] ?? null) : null;
            if (!is_string($xclassName) || $xclassName === '') {
                continue;
            }
            [$severity, $message] = $this->xclassVerdict($className, $xclassName);
            $findings[] = new ConfigurationFinding(
                ConfigurationFindingKind::Xclass,
                $severity,
                $className,
                $message,
            );
        }

        return $findings;
    }

    /**
     * @return array{ContextualFeedbackSeverity, string}
     */
    private function xclassVerdict(string $className, string $xclassName): array
    {
        if (!class_exists($className)) {
            return [
                ContextualFeedbackSeverity::ERROR,
                sprintf(
                    'The class "%s" is replaced by "%s" through the XCLASS registry, but the installed '
                    . 'version does not ship it any more. The replacement is never instantiated, and it '
                    . 'is a fatal error as soon as anything loads it.',
                    $className,
                    $xclassName,
                ),
            ];
        }
        if ((new \ReflectionClass($className))->isFinal()) {
            return [
                ContextualFeedbackSeverity::ERROR,
                sprintf(
                    'The class "%s" is replaced by "%s" through the XCLASS registry, and it is final: a '
                    . 'subclass of it is a fatal error. Achieve the change through an event listener, a '
                    . 'service decoration or a replacement service instead.',
                    $className,
                    $xclassName,
                ),
            ];
        }

        return [
            ContextualFeedbackSeverity::WARNING,
            sprintf(
                'The class "%s" is replaced by "%s" through the XCLASS registry. None of the academic '
                . 'extensions is an API for subclassing, so the replacement breaks on any release. Achieve '
                . 'the change through an event listener, a service decoration or a replacement service '
                . 'instead.',
                $className,
                $xclassName,
            ),
        ];
    }

    /**
     * The `sys_template` rows of the whole installation, with the restrictions
     * core applies when it builds the TypoScript of a page without a preview:
     * a deleted, hidden or expired record delivers nothing, so there is nothing
     * to report about it.
     *
     * Every row is read, not only the ones that include a static template: the
     * `constants` and `config` fields and the `clear` flag are checked too, and
     * a record can carry any of them without the other.
     *
     * @return list<array{uid: int, pid: int, title: string, clear: int, constants: string, config: string, include_static_file: string}>
     */
    private function sysTemplateRows(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $result = $queryBuilder
            ->select('uid', 'pid', 'title', 'clear', 'constants', 'config', 'include_static_file')
            ->from('sys_template')
            ->orderBy('uid')
            ->executeQuery();

        $rows = [];
        while ($row = $result->fetchAssociative()) {
            $rows[] = [
                'uid' => (int)$row['uid'],
                'pid' => (int)$row['pid'],
                'title' => (string)$row['title'],
                'clear' => (int)($row['clear'] ?? 0),
                'constants' => (string)($row['constants'] ?? ''),
                'config' => (string)($row['config'] ?? ''),
                'include_static_file' => (string)($row['include_static_file'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * The pages holding page TSconfig of their own.
     *
     * Only the deleted restriction is applied here, because page TSconfig of a
     * hidden page is read all the same - `BackendUtility::getPagesTSconfig()`
     * walks a rootline that a hidden page is part of.
     *
     * **Only the live, default language rows.** Both columns are `l10n_mode` =
     * `exclude` in the core TCA, so `DataMapProcessor` writes a byte copy of
     * the default language value into every translation, and a workspace
     * version of a page carries its own copy as well. Core reads page TSconfig
     * from neither: `PageRepository` keeps the default language uid in the
     * rootline and moves the translation's into `_LOCALIZED_UID`, and
     * `versionOL()` restores the live uid over the versioned one. Reporting
     * either would name a record that is not the one to correct, once per
     * language and once per workspace.
     *
     * `sys_template` needs no such condition: its TCA `ctrl` declares no
     * `versioningWS` and no `languageField` on either version, so there is
     * nothing but live default language rows in it to begin with.
     *
     * Reading the live rows means reading what the installation delivers
     * today, which is the question this check answers. The consequence is that
     * a stale import introduced in a workspace is reported once it is
     * published, and one corrected in a workspace is reported until then.
     *
     * @return list<array{uid: int, title: string, TSconfig: string, tsconfig_includes: string}>
     */
    private function pageRows(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
        $result = $queryBuilder
            ->select('uid', 'title', 'TSconfig', 'tsconfig_includes')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->neq('TSconfig', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->neq('tsconfig_includes', $queryBuilder->createNamedParameter('')),
                ),
            )
            ->orderBy('uid')
            ->executeQuery();

        $rows = [];
        while ($row = $result->fetchAssociative()) {
            $rows[] = [
                'uid' => (int)$row['uid'],
                'title' => (string)$row['title'],
                'TSconfig' => (string)($row['TSconfig'] ?? ''),
                'tsconfig_includes' => (string)($row['tsconfig_includes'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * The academic extension key an `EXT:` path names, or `null` when the path
     * is none of this check's business.
     */
    private function academicExtensionKeyOf(string $path): ?string
    {
        if (!PathUtility::isExtensionPath($path)) {
            return null;
        }
        $extensionKey = explode('/', substr($path, strlen('EXT:')), 2)[0];

        return $this->isAcademicExtensionKey($extensionKey) ? $extensionKey : null;
    }

    private function isAcademicExtensionKey(string $extensionKey): bool
    {
        return str_starts_with($extensionKey, self::EXTENSION_KEY_PREFIX)
            || in_array($extensionKey, self::ADDITIONAL_EXTENSION_KEYS, true);
    }

    private function isAcademicClassName(string $className): bool
    {
        foreach (self::CLASS_NAME_PREFIXES as $prefix) {
            if (str_starts_with($className, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
