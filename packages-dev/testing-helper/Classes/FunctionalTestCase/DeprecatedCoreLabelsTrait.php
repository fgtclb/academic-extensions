<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

/**
 * Guards against TCA pointing at core labels that TYPO3 v14 retired.
 *
 * TYPO3 v14 marks a set of labels `x-unused-since="14.0"` (#107938, #108086).
 * They still resolve there, through an `.x-unused` fallback that raises
 * `E_USER_DEPRECATED` on every backend form render — but the v14 language packs
 * no longer ship translations for them, so a German backend falls back to the
 * English source. The replacements core offers live in v14-only XLIFF 2.0 files
 * and cannot be referenced while TYPO3 v13 is supported, so the extensions ship
 * these labels themselves (ACE-298).
 *
 * The assertion walks the *compiled* TCA rather than the source files, so it
 * also covers labels that are assembled at runtime.
 *
 * **Assert this on TYPO3 v14 only** (`#[Group('not-core-13')]`). On v13 the
 * labels are not deprecated at all, and core's own `TcaEnrichment` adds them to
 * every table that declares `transOrigPointerField` without defining the column
 * — so a v13 run reports core's labels, which are none of our business. On v14
 * that same enrichment uses `core.db.general:l18n_parent`, so whatever the scan
 * finds there is genuinely ours.
 */
trait DeprecatedCoreLabelsTrait
{
    /**
     * Core label references retired in TYPO3 v14, mapped to what replaced them
     * in `EXT:academic_base/Resources/Private/Language/locallang_tca.xlf`.
     *
     * @var array<string, string>
     */
    private array $deprecatedCoreLabels = [
        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent' => 'l18n_parent',
        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login' => 'fe_group.hide_at_login',
        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login' => 'fe_group.any_login',
        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups' => 'fe_group.usergroups',
        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general' => 'palette.general',
        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access' => 'palette.access',
        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden' => 'field.hidden',
    ];

    /**
     * @param string[] $tablePrefixes Tables to inspect, by prefix
     */
    private function assertTcaHasNoDeprecatedCoreLabelReferences(array $tablePrefixes): void
    {
        $findings = [];
        foreach ($GLOBALS['TCA'] ?? [] as $table => $configuration) {
            foreach ($tablePrefixes as $prefix) {
                if (str_starts_with((string)$table, $prefix)) {
                    $findings = [...$findings, ...$this->findDeprecatedLabels($configuration, (string)$table)];
                    break;
                }
            }
        }

        self::assertSame(
            [],
            $findings,
            'TCA references core labels that TYPO3 v14 retired. Use the replacement in'
            . ' EXT:academic_base/Resources/Private/Language/locallang_tca.xlf instead:' . PHP_EOL
            . implode(PHP_EOL, $findings),
        );
    }

    /**
     * @param mixed $configuration
     * @return string[]
     */
    private function findDeprecatedLabels(mixed $configuration, string $path): array
    {
        if (is_string($configuration)) {
            foreach ($this->deprecatedCoreLabels as $deprecated => $replacement) {
                if (str_contains($configuration, $deprecated)) {
                    return [sprintf('  %s: %s -> use "%s"', $path, $deprecated, $replacement)];
                }
            }
            return [];
        }
        if (!is_array($configuration)) {
            return [];
        }
        $findings = [];
        foreach ($configuration as $key => $value) {
            $findings = [...$findings, ...$this->findDeprecatedLabels($value, $path . '.' . $key)];
        }
        return $findings;
    }
}
