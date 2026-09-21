<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Upgrade;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Reports the stored configuration of an installation that no longer reaches
 * the academic extensions after an upgrade.
 *
 * None of these fails loudly. TYPO3 skips a static template folder that holds
 * no TypoScript, an `@import` that matches no file and a `tsconfig_includes`
 * entry whose file is gone without a word, so an upgrade that renames a folder
 * leaves an installation that looks configured and is not. The three remaining
 * checks cover the opposite mistake: configuration that arrives twice, or that
 * replaces a class the extension has since made `final`.
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

    private const PAGE_TSCONFIG_FILE_SUFFIX = 'tsconfig';

    public function __construct(
        private PackageManager $packageManager,
        private ConnectionPool $connectionPool,
        private SiteFinder $siteFinder,
    ) {}

    /**
     * @return list<ConfigurationFinding>
     */
    public function check(): array
    {
        $sysTemplateRows = $this->sysTemplateRows();

        return [
            ...$this->checkStaticTemplates($sysTemplateRows),
            ...$this->checkPageTsConfig(),
            ...$this->checkSites($sysTemplateRows),
            ...$this->checkXclasses(),
        ];
    }

    /**
     * @param list<array{uid: int, pid: int, title: string, include_static_file: string}> $sysTemplateRows
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
            $location = sprintf('The page %d ("%s")', $row['uid'], $row['title']);
            foreach (GeneralUtility::trimExplode(',', $row['tsconfig_includes'], true) as $value) {
                $findings = [...$findings, ...$this->checkTsConfigInclude($subject, $location, $value)];
            }
            $findings = [...$findings, ...$this->checkTsConfigContent($subject, $location, $row['TSconfig'])];
        }

        $sites = $this->siteFinder->getAllSites();
        ksort($sites, SORT_STRING);
        foreach ($sites as $site) {
            $findings = [
                ...$findings,
                ...$this->checkTsConfigContent(
                    sprintf('site:%s', $site->getIdentifier()),
                    sprintf('The file "config/sites/%s/page.tsconfig"', $site->getIdentifier()),
                    $site->getTSconfig()?->pageTSconfig ?? '',
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
     * @return list<ConfigurationFinding>
     */
    private function checkTsConfigInclude(string $subject, string $location, string $value): array
    {
        $extensionKey = $this->academicExtensionKeyOf($value);
        if ($extensionKey === null) {
            return [];
        }
        if (!$this->packageManager->isPackageActive($extensionKey)) {
            return [$this->tsConfigImportFinding($subject, sprintf(
                '%s selects the page TSconfig file "%s", but the extension "%s" is not installed.',
                $location,
                $value,
                $extensionKey,
            ))];
        }
        $extensionPath = rtrim($this->packageManager->getPackage($extensionKey)->getPackagePath(), '/') . '/';
        $fileName = PathUtility::getCanonicalPath($extensionPath . substr($value, strlen('EXT:' . $extensionKey) + 1));
        if (str_starts_with($fileName, $extensionPath) && is_file($fileName)) {
            return [];
        }

        return [$this->tsConfigImportFinding($subject, sprintf(
            '%s selects the page TSconfig file "%s", which the installed version of "%s" does not ship.',
            $location,
            $value,
            $extensionKey,
        ))];
    }

    /**
     * The academic references of one page TSconfig string.
     *
     * @return list<ConfigurationFinding>
     */
    private function checkTsConfigContent(string $subject, string $location, string $tsConfig): array
    {
        if (trim($tsConfig) === '') {
            return [];
        }
        $findings = [];
        foreach ($this->tsConfigReferences($tsConfig) as $reference) {
            $extensionKey = $this->academicExtensionKeyOf($reference['path']);
            if ($extensionKey === null) {
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
            if ($this->atImportResolves($reference['path'])) {
                continue;
            }
            if (!$this->packageManager->isPackageActive($extensionKey)) {
                $findings[] = $this->tsConfigImportFinding($subject, sprintf(
                    '%s imports "%s", but the extension "%s" is not installed. TYPO3 skips the import '
                    . 'without a message.',
                    $location,
                    $reference['path'],
                    $extensionKey,
                ));
                continue;
            }
            $findings[] = $this->tsConfigImportFinding($subject, sprintf(
                '%s imports "%s", which matches no file of the installed version. TYPO3 skips '
                . 'the import without a message.',
                $location,
                $reference['path'],
            ));
        }

        return $findings;
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
     * Whether an `@import` of page TSconfig reads at least one file.
     *
     * The four shapes are the ones `TreeFromLineStreamBuilder::processAtImport()`
     * handles for a string that is not itself a file: an exact file, a folder,
     * a file name without its suffix, and one wildcard in the file name. The
     * relative lookup of that method is deliberately not reproduced - it needs
     * the path of the *file* an import stands in, and page TSconfig from the
     * database has none, so a relative import there never resolves anyway.
     */
    private function atImportResolves(string $value): bool
    {
        $absolutePath = rtrim(GeneralUtility::getFileAbsFileName($value), '/');
        if ($absolutePath === '') {
            return false;
        }
        if (str_ends_with($absolutePath, '.' . self::PAGE_TSCONFIG_FILE_SUFFIX) && is_file($absolutePath)) {
            return true;
        }
        if (is_dir($absolutePath)) {
            return $this->folderHoldsAMatch($absolutePath . '/', '', '.' . self::PAGE_TSCONFIG_FILE_SUFFIX);
        }
        if (is_file($absolutePath . '.' . self::PAGE_TSCONFIG_FILE_SUFFIX)) {
            return true;
        }
        if (!str_contains($absolutePath, '*')) {
            return false;
        }
        $folder = rtrim(dirname($absolutePath), '/') . '/';
        $filePattern = basename($absolutePath);
        if (!is_dir($folder) || substr_count($filePattern, '*') !== 1) {
            return false;
        }
        if (str_ends_with($filePattern, self::PAGE_TSCONFIG_FILE_SUFFIX)) {
            $filePattern = rtrim(substr($filePattern, 0, -strlen(self::PAGE_TSCONFIG_FILE_SUFFIX)), '.');
        }
        $filePattern .= '.' . self::PAGE_TSCONFIG_FILE_SUFFIX;
        $wildcardPosition = (int)strpos($filePattern, '*');

        return $this->folderHoldsAMatch(
            $folder,
            substr($filePattern, 0, $wildcardPosition),
            substr($filePattern, $wildcardPosition + 1),
        );
    }

    private function folderHoldsAMatch(string $folder, string $prefix, string $suffix): bool
    {
        foreach ((array)(scandir($folder) ?: []) as $entry) {
            $entry = (string)$entry;
            if ($entry === '.' || $entry === '..' || is_dir($folder . $entry)) {
                continue;
            }
            if (str_starts_with($entry, $prefix) && str_ends_with($entry, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The two checks a site configuration carries: a dependency on an alias
     * set, and one extension delivered through a set and through a static
     * template at the same time.
     *
     * Only the *declared* dependencies of a site are looked at, because that is
     * what `Site::getSets()` answers - a set reached through another one is not
     * in the list, and is a deliberate choice of whoever wrote the set that
     * pulls it in.
     *
     * @param list<array{uid: int, pid: int, title: string, include_static_file: string}> $sysTemplateRows
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
                if (isset(self::ALIAS_SETS[$setName])) {
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
            if ($extensionKeysWithASet === []) {
                continue;
            }
            $rootPageId = $site->getRootPageId();
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
     * @return list<array{uid: int, pid: int, title: string, include_static_file: string}>
     */
    private function sysTemplateRows(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $result = $queryBuilder
            ->select('uid', 'pid', 'title', 'include_static_file')
            ->from('sys_template')
            ->where($queryBuilder->expr()->neq(
                'include_static_file',
                $queryBuilder->createNamedParameter(''),
            ))
            ->orderBy('uid')
            ->executeQuery();

        $rows = [];
        while ($row = $result->fetchAssociative()) {
            $rows[] = [
                'uid' => (int)$row['uid'],
                'pid' => (int)$row['pid'],
                'title' => (string)$row['title'],
                'include_static_file' => (string)$row['include_static_file'],
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
