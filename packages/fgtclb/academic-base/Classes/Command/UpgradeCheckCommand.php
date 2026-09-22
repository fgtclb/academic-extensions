<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Command;

use FGTCLB\AcademicBase\Upgrade\ConfigurationChecker;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFinding;
use FGTCLB\AcademicBase\Upgrade\TemplateOverrideChecker;
use FGTCLB\AcademicBase\Upgrade\TemplateOverrideFinding;
use FGTCLB\AcademicBase\Upgrade\TemplateOverrideFindingKind;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Reports what an upgrade of the academic extensions left behind in a project:
 * stored configuration that no longer reaches them, and project template
 * overrides that no longer take effect or that only freeze the upstream markup.
 *
 * The configuration group runs on every invocation and needs no argument - it
 * reads the TypoScript records, the page TSconfig, the site configurations and
 * the XCLASS registry of the whole installation. The template override group
 * needs to be pointed at something, so it runs when an extension key is named.
 *
 * An override folder is a folder a project adds to the view root paths of the
 * extension's plugins. Nothing in TYPO3 says a file in it is never resolved: a
 * template the extension removed, and a name that differs from the upstream one
 * only in case, are both silently ignored - the second one only on the Linux
 * server, not on the development machine.
 *
 * The folders can be named directly, or be taken from the TypoScript of a site
 * with `--site`. The command only reads.
 */
#[AsCommand(
    name: 'academic:upgrade:check',
    description: 'Report stored configuration and project template overrides the academic extensions no longer match.',
)]
final class UpgradeCheckCommand extends Command
{
    /**
     * The upstream folder each kind of view root path is compared with, below
     * the extension's `Resources/Private/`.
     *
     * @var array<string, string>
     */
    private const ROOT_PATH_KINDS = [
        'templateRootPaths' => 'Templates',
        'partialRootPaths' => 'Partials',
        'layoutRootPaths' => 'Layouts',
    ];

    public function __construct(
        private readonly ConfigurationChecker $configurationChecker,
        private readonly PackageManager $packageManager,
        private readonly SiteFinder $siteFinder,
        private readonly StateManagerInterface $stateManager,
        private readonly TemplateOverrideChecker $checker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(implode("\n", [
                'Runs two groups of checks.',
                '',
                'The configuration group always runs and takes no argument. It reads the stored',
                'configuration of the installation and reports what no longer reaches the',
                'academic extensions, or what reaches them twice:',
                '',
                '  static-template          a TypoScript record includes a static template that',
                '                           delivers no TypoScript any more',
                '  tsconfig-import          a page or a site imports a page TSconfig file that is',
                '                           not there',
                '  tsconfig-syntax          a page or a site uses "<INCLUDE_TYPOSCRIPT:", which',
                '                           TYPO3 v14 no longer reads',
                '  alias-set                a site depends on a set that only forwards to another',
                '  set-and-static-template  a site delivers one extension through both mechanisms',
                '  xclass                   an academic class is replaced through the XCLASS registry',
                '',
                'The template override group runs when an extension key is named. It compares the',
                'Fluid files below one or more override folders with those the installed extension',
                'ships, and reports every file that no longer matches one:',
                '',
                '  missing-upstream  the extension ships no such file - the override is dead',
                '  case-mismatch     the extension ships it under a name differing only in case',
                '  identical         a byte identical copy, which freezes the upstream markup',
                '',
                'Everything but a notice is a problem and lets the command exit with 1.',
                '',
                'Examples:',
                '',
                '  academic:upgrade:check',
                '',
                '  academic:upgrade:check academic_persons_edit \\',
                '      --override-path=EXT:my_site/Resources/Private/Extensions/AcademicPersonsEdit/',
                '',
                '  academic:upgrade:check academic_persons \\',
                '      --override-path=EXT:my_site/Resources/Private/Partials/Academic/ \\',
                '      --upstream-path=EXT:academic_persons/Resources/Private/Partials/',
                '',
                '  academic:upgrade:check academic_persons --site=main',
            ]))
            ->addArgument(
                'extension',
                InputArgument::OPTIONAL,
                'Extension key of the academic extension the overrides belong to. Without it only '
                . 'the configuration of the installation is checked.',
            )
            ->addOption(
                'override-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Folder holding the project overrides, as an "EXT:" path or an absolute path. Repeatable.',
            )
            ->addOption(
                'upstream-path',
                null,
                InputOption::VALUE_REQUIRED,
                'Folder the override folders are compared with. Default: "EXT:<extension>/Resources/Private/".',
            )
            ->addOption(
                'site',
                null,
                InputOption::VALUE_REQUIRED,
                'Take the override folders from the TypoScript view root paths of this site.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $extensionKey = $input->getArgument('extension');
        $extensionKey = is_string($extensionKey) && $extensionKey !== '' ? $extensionKey : null;
        /** @var list<string> $overridePaths */
        $overridePaths = $input->getOption('override-path');
        $siteIdentifier = $input->getOption('site');
        $siteIdentifier = is_string($siteIdentifier) && $siteIdentifier !== '' ? $siteIdentifier : null;
        $upstreamPathOption = $input->getOption('upstream-path');
        $upstreamPathOption = is_string($upstreamPathOption) && $upstreamPathOption !== ''
            ? $upstreamPathOption
            : null;

        if ($extensionKey === null) {
            // Every option of this command belongs to the template override
            // group. Accepting one without the extension key would silently
            // ignore it, and before the argument became optional Symfony
            // refused the call outright.
            if ($overridePaths !== [] || $siteIdentifier !== null || $upstreamPathOption !== null) {
                $output->writeln(
                    '<error>--override-path, --upstream-path and --site name the override folders of one '
                    . 'extension, so the extension key has to be given as well.</error>',
                );
                return Command::INVALID;
            }
        } elseif (!$this->packageManager->isPackageActive($extensionKey)) {
            $output->writeln(sprintf('<error>The extension "%s" is not active.</error>', $extensionKey));
            return Command::INVALID;
        } elseif ($overridePaths === [] && $upstreamPathOption !== null) {
            // It names the folder the --override-path folders are compared
            // with, and nothing else: the folders a --site names are compared
            // with the upstream folder of their own kind. Accepting it here
            // would ignore it, which is the failure mode this command exists
            // to remove.
            $output->writeln(
                '<error>--upstream-path names the folder that the --override-path folders are compared '
                . 'with, so it needs at least one --override-path. The folders a --site names are '
                . 'compared with the upstream folder of their own kind.</error>',
            );
            return Command::INVALID;
        } elseif ($overridePaths === [] && $siteIdentifier === null) {
            $output->writeln('<error>Name at least one --override-path, or a --site to take them from.</error>');
            return Command::INVALID;
        }

        $configurationProblems = $this->reportConfiguration($output);
        if ($extensionKey === null) {
            return $configurationProblems > 0 ? Command::FAILURE : Command::SUCCESS;
        }

        $invalidInput = false;
        /** @var list<array{label: string, upstreamLabel: string, override: string, upstream: string}> $comparisons */
        $comparisons = [];

        if ($overridePaths !== []) {
            $upstreamPath = $upstreamPathOption ?? sprintf('EXT:%s/Resources/Private/', $extensionKey);
            $upstreamFolder = $this->resolveFolder($upstreamPath);
            if ($upstreamFolder === null) {
                $output->writeln(sprintf(
                    '<error>The upstream folder "%s" %s</error>',
                    $upstreamPath,
                    $this->reasonFolderCannotBeUsed($upstreamPath),
                ));
                return Command::INVALID;
            }
            foreach ($overridePaths as $overridePath) {
                $overrideFolder = $this->resolveFolder($overridePath);
                if ($overrideFolder === null) {
                    $output->writeln(sprintf(
                        '<error>The override folder "%s" %s</error>',
                        $overridePath,
                        $this->reasonFolderCannotBeUsed($overridePath),
                    ));
                    $invalidInput = true;
                    continue;
                }
                $comparisons[] = [
                    'label' => $overridePath,
                    'upstreamLabel' => $upstreamPath,
                    'override' => $overrideFolder,
                    'upstream' => $upstreamFolder,
                ];
            }
        }

        if ($siteIdentifier !== null) {
            try {
                // A site that cannot be read invalidates the run, but not the
                // folders the command line named: those were checked, and
                // throwing their findings away would answer "nothing to report"
                // for a project that has plenty.
                $comparisons = [
                    ...$comparisons,
                    ...$this->comparisonsOfSite($siteIdentifier, $extensionKey, $output, $invalidInput),
                ];
            } catch (\RuntimeException $exception) {
                $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
                $invalidInput = true;
            }
        }

        $output->writeln('');
        $output->writeln('<comment>Template overrides</comment>');
        $problems = 0;
        $notices = 0;
        foreach ($comparisons as $comparison) {
            $findings = $this->checker->check($comparison['override'], $comparison['upstream']);
            $output->writeln('');
            $output->writeln(sprintf('<info>%s</info>', $comparison['label']));
            $output->writeln(sprintf('  compared with %s', $comparison['upstreamLabel']));
            if ($findings === []) {
                $output->writeln('  nothing to report');
                continue;
            }
            foreach ($findings as $finding) {
                $problems += $finding->kind->isProblem() ? 1 : 0;
                $notices += $finding->kind->isProblem() ? 0 : 1;
                $output->writeln('  ' . $this->format($finding));
            }
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '%d problem%s and %d notice%s in %d override folder%s.',
            $problems,
            $problems === 1 ? '' : 's',
            $notices,
            $notices === 1 ? '' : 's',
            count($comparisons),
            count($comparisons) === 1 ? '' : 's',
        ));

        if ($invalidInput) {
            return Command::INVALID;
        }

        return $problems + $configurationProblems > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * The configuration group, which takes no argument and checks the whole
     * installation.
     *
     * @return int The number of findings that are more than a notice.
     */
    private function reportConfiguration(OutputInterface $output): int
    {
        $output->writeln('<comment>Configuration</comment>');
        $findings = $this->configurationChecker->check();
        if ($findings === []) {
            $output->writeln('');
            $output->writeln('  nothing to report');

            return 0;
        }

        $problems = 0;
        $notices = 0;
        foreach ($findings as $finding) {
            $problems += $finding->isProblem() ? 1 : 0;
            $notices += $finding->isProblem() ? 0 : 1;
            $output->writeln('');
            $output->writeln('  ' . $this->formatConfigurationFinding($finding));
            $output->writeln('    ' . $finding->message);
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '%d problem%s and %d notice%s in the stored configuration.',
            $problems,
            $problems === 1 ? '' : 's',
            $notices,
            $notices === 1 ? '' : 's',
        ));

        return $problems;
    }

    private function formatConfigurationFinding(ConfigurationFinding $finding): string
    {
        return sprintf(
            '%s %-24s %s',
            match ($finding->severity) {
                ContextualFeedbackSeverity::ERROR => 'x',
                ContextualFeedbackSeverity::WARNING => '!',
                default => 'i',
            },
            $finding->kind->value,
            $finding->subject,
        );
    }

    private function format(TemplateOverrideFinding $finding): string
    {
        $line = sprintf(
            '%s %-17s %s',
            match ($finding->kind) {
                TemplateOverrideFindingKind::MissingUpstream => 'x',
                TemplateOverrideFindingKind::CaseMismatch => '!',
                TemplateOverrideFindingKind::Identical => '=',
            },
            $finding->kind->value,
            $finding->overridePath,
        );
        if ($finding->kind === TemplateOverrideFindingKind::CaseMismatch) {
            $line .= ' -> ' . (string)$finding->upstreamPath;
        }

        return $line;
    }

    /**
     * Takes the override folders of one site from the view root paths its
     * TypoScript configures for the extension's plugins.
     *
     * A root path that lies inside the extension itself, inside a package it
     * requires or inside a TYPO3 system extension is what the plugin ships,
     * not a project override - the shared partials of academic_base sit in the
     * `partialRootPaths` of every academic plugin, and `fluid_styled_content`
     * does so for academic_persons_edit. Every other root path is a project
     * override folder.
     *
     * A root path naming a folder that does not exist is reported and skipped,
     * and `$invalidInput` is set so the run ends with an error status: the
     * other root paths of the site are still checked, because a single broken
     * entry must not hide the findings of the rest.
     *
     * @return list<array{label: string, upstreamLabel: string, override: string, upstream: string}>
     * @throws \RuntimeException When the site or its frontend environment cannot be resolved.
     */
    private function comparisonsOfSite(
        string $siteIdentifier,
        string $extensionKey,
        OutputInterface $output,
        bool &$invalidInput,
    ): array {
        try {
            $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
        } catch (SiteNotFoundException) {
            throw new \RuntimeException(sprintf('There is no site "%s".', $siteIdentifier), 1758470401);
        }

        $setup = $this->setupOfSite($site->getRootPageId(), $siteIdentifier);
        $pluginSignature = 'tx_' . str_replace('_', '', $extensionKey);
        /** @var array<string, mixed> $view */
        $view = $setup['plugin.'][$pluginSignature . '.']['view.'] ?? [];

        $configuresAnyRootPath = false;
        foreach (array_keys(self::ROOT_PATH_KINDS) as $rootPathsKey) {
            $configuresAnyRootPath = $configuresAnyRootPath
                || (is_array($view[$rootPathsKey . '.'] ?? null) && $view[$rootPathsKey . '.'] !== []);
        }
        if (!$configuresAnyRootPath) {
            throw new \RuntimeException(
                sprintf(
                    'The TypoScript of the site "%s" configures no view root path below "plugin.%s.view". '
                    . 'Either the site does not use this extension, or its TypoScript is not included.',
                    $siteIdentifier,
                    $pluginSignature,
                ),
                1758470404,
            );
        }

        $upstreamPackagePaths = $this->upstreamPackagePaths($extensionKey);
        $comparisons = [];
        foreach (self::ROOT_PATH_KINDS as $rootPathsKey => $upstreamFolderName) {
            $rootPaths = $view[$rootPathsKey . '.'] ?? [];
            if (!is_array($rootPaths)) {
                continue;
            }
            $upstreamLabel = sprintf('EXT:%s/Resources/Private/%s/', $extensionKey, $upstreamFolderName);
            // An extension that ships no folder of this kind - academic_base has
            // neither "Templates/" nor "Layouts/" - makes every file of the
            // project's folder a dead override, which is the correct answer. The
            // checker treats a folder that is not there as an empty one, so the
            // absolute path of the folder the extension would ship is handed on.
            $upstreamFolder = $this->resolveFolder($upstreamLabel)
                ?? rtrim($this->packageManager->getPackage($extensionKey)->getPackagePath(), '/')
                    . '/Resources/Private/' . $upstreamFolderName . '/';
            ksort($rootPaths, SORT_NATURAL);
            foreach ($rootPaths as $key => $rootPath) {
                if (!is_string($rootPath) || $rootPath === '') {
                    continue;
                }
                $typoScriptPath = sprintf('plugin.%s.view.%s.%s', $pluginSignature, $rootPathsKey, $key);
                $overrideFolder = $this->resolveFolder($rootPath);
                if ($overrideFolder === null) {
                    $output->writeln(sprintf(
                        '<error>%s names the folder "%s", which %s</error>',
                        $typoScriptPath,
                        $rootPath,
                        $this->reasonFolderCannotBeUsed($rootPath),
                    ));
                    $invalidInput = true;
                    continue;
                }
                if ($this->isBelowOneOf($overrideFolder, $upstreamPackagePaths)) {
                    continue;
                }
                $comparisons[] = [
                    'label' => sprintf('%s (%s)', $rootPath, $typoScriptPath),
                    'upstreamLabel' => $upstreamLabel,
                    'override' => $overrideFolder,
                    'upstream' => $upstreamFolder,
                ];
            }
        }

        return $comparisons;
    }

    /**
     * @return array<string, mixed> The setup array of the site's root page.
     * @throws \RuntimeException When the frontend environment of the site cannot be built.
     */
    private function setupOfSite(int $rootPageId, string $siteIdentifier): array
    {
        $setup = null;
        try {
            $this->stateManager->execute(
                new StateBuildContext(ApplicationType::FRONTEND, $rootPageId),
                static function () use (&$setup): void {
                    $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
                    $frontendTypoScript = $request instanceof \Psr\Http\Message\ServerRequestInterface
                        ? $request->getAttribute('frontend.typoscript')
                        : null;
                    if ($frontendTypoScript instanceof FrontendTypoScript && $frontendTypoScript->hasSetup()) {
                        $setup = $frontendTypoScript->getSetupArray();
                    }
                },
            );
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                sprintf(
                    'The frontend environment of the site "%s" could not be built: %s',
                    $siteIdentifier,
                    $exception->getMessage(),
                ),
                1758470402,
                $exception,
            );
        }
        if ($setup === null) {
            throw new \RuntimeException(
                sprintf(
                    'The site "%s" has no frontend TypoScript setup. It needs a root TypoScript record or a site set.',
                    $siteIdentifier,
                ),
                1758470403,
            );
        }

        return $setup;
    }

    /**
     * The package paths whose files are upstream rather than a project override:
     * the extension itself, everything it requires, and the TYPO3 system
     * extensions.
     *
     * The requirements are read from the package's composer manifest rather
     * than from `MetaData::getConstraintsByType('depends')`. That list is
     * derived, and what it holds depends on the core version *and* on how the
     * package was registered - the `depends` of academic_base is
     * `php symfony/serializer core extbase` in a TYPO3 v13 composer artifact,
     * `typo3/cms-core typo3/cms-extbase` in a v14 one, and
     * `core backend extbase environment_state_manager` in a v13 functional
     * test instance, which derives it from `ext_emconf.php`. Worse, a package
     * registered at runtime on v14 loses a requirement that composer already
     * installed, so a fixture requiring `fgtclb/academic-base` gets
     * `['typo3/cms-core']` - without the entry this rule needs.
     *
     * The manifest `require` is the same list in all of them;
     * `getPackageKeyFromComposerName()` accepts both spellings, and a name
     * that is no active package is skipped. Both methods are `@internal` on
     * v13 and v14 - there is no public API for the question, so this is a
     * deliberate use of internal API and a TYPO3 v15 re-check item.
     *
     * @return list<string>
     */
    private function upstreamPackagePaths(string $extensionKey): array
    {
        $paths = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            if ($package->getPackageMetaData()->isFrameworkType()) {
                $paths[] = rtrim($package->getPackagePath(), '/') . '/';
            }
        }

        $seen = [];
        $queue = [$extensionKey];
        while ($queue !== []) {
            $key = array_shift($queue);
            if (isset($seen[$key]) || !$this->packageManager->isPackageActive($key)) {
                continue;
            }
            $seen[$key] = true;
            $package = $this->packageManager->getPackage($key);
            $paths[] = rtrim($package->getPackagePath(), '/') . '/';
            $requirements = $package->getValueFromComposerManifest('require');
            foreach (array_keys((array)($requirements ?? [])) as $requirement) {
                $queue[] = $this->packageManager->getPackageKeyFromComposerName((string)$requirement);
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param list<string> $folders
     */
    private function isBelowOneOf(string $folder, array $folders): bool
    {
        foreach ($folders as $candidate) {
            if (str_starts_with($folder, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string|null The absolute folder with a trailing slash, or `null` when it cannot be used.
     */
    private function resolveFolder(string $path): ?string
    {
        $absolutePath = GeneralUtility::getFileAbsFileName($path);
        if ($absolutePath === '' || !is_dir($absolutePath)) {
            return null;
        }

        return rtrim($absolutePath, '/') . '/';
    }

    /**
     * Why a folder could not be used, for a message that does not send the
     * integrator looking for a folder that is plainly there.
     *
     * `GeneralUtility::getFileAbsFileName()` answers `''` for three different
     * mistakes, and they need three different answers: an `EXT:` path naming an
     * extension that is not installed or not active, an absolute path outside
     * the project root, and a path containing `../`. Telling somebody who
     * mistyped an extension key to "name it as an EXT: path" is the one answer
     * that helps nobody.
     */
    private function reasonFolderCannotBeUsed(string $path): string
    {
        if (GeneralUtility::getFileAbsFileName($path) !== '') {
            return 'does not exist.';
        }
        if (PathUtility::isExtensionPath($path)) {
            return 'names an extension that is not installed or not active.';
        }

        return sprintf(
            'cannot be resolved. Name it as an "EXT:" path, or as an absolute path inside "%s".',
            Environment::getProjectPath(),
        );
    }
}
