<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Report;

use FGTCLB\AcademicBase\Upgrade\ConfigurationChecker;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Shows what {@see ConfigurationChecker} finds in the status report of
 * EXT:reports, so an integrator who never runs
 * `academic:upgrade:check` on the command line sees it too.
 *
 * Registered by `Configuration/Services.php` only when EXT:reports is loaded,
 * because the interface belongs to that extension; EXT:reports itself tags
 * every implementation as a status provider.
 *
 * The finding texts are the ones the command prints, in English. They are
 * built from record uids, stored paths and class names and are read next to
 * the command output, so one wording for both is worth more here than a
 * translated one for each.
 *
 * @internal not part of public API.
 */
final class UpgradeConfigurationStatus implements StatusProviderInterface
{
    private const LANGUAGE_FILE = 'LLL:EXT:academic_base/Resources/Private/Language/locallang_reports.xlf:';

    public function __construct(
        private readonly ConfigurationChecker $configurationChecker,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /**
     * The status report resolves a label that is a language reference itself.
     */
    public function getLabel(): string
    {
        return self::LANGUAGE_FILE . 'status.label';
    }

    /**
     * @return Status[]
     */
    public function getStatus(): array
    {
        $statuses = [];
        foreach ($this->configurationChecker->check() as $finding) {
            $statuses[] = new Status(
                $finding->kind->title(),
                $finding->subject,
                $finding->message,
                $finding->severity,
            );
        }
        if ($statuses === []) {
            $languageService = $this->getLanguageService();
            $statuses[] = new Status(
                $languageService->sL(self::LANGUAGE_FILE . 'status.upgradeConfiguration.none.title'),
                $languageService->sL(self::LANGUAGE_FILE . 'status.upgradeConfiguration.none.value'),
                $languageService->sL(self::LANGUAGE_FILE . 'status.upgradeConfiguration.none.message'),
            );
        }

        return $statuses;
    }

    private function getLanguageService(): LanguageService
    {
        return $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }
}
