<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Form;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * The redirect page and the flash message of a frontend form plugin after it saved a
 * record, read from the plugin settings.
 *
 * Both come from the content element first, `settings.redirectPageId` and
 * `settings.flashMessageCreationMode` of the plugin FlexForm, and from TypoScript
 * otherwise, `settings.saveForm.fallbackRedirectPageId` and
 * `settings.saveForm.fallbackFlashMessageCreationMode`. A controller resolves both
 * before it dispatches its after-save event, so that a listener may replace them, and
 * calls {@see self::decide()} with what the listeners left.
 *
 * @internal for the frontend form plugins of the academic extensions, not part of the public API.
 */
final readonly class AfterSaveResolver
{
    /**
     * The page to redirect to, or null for none. Once the plugin setting holds an
     * integer it decides alone: a page id of 0 or below there means no redirect page,
     * and the TypoScript fallback is not consulted.
     *
     * @param array<string, mixed> $settings
     */
    public function redirectPageId(array $settings): ?int
    {
        $pluginValue = $this->integerSetting($settings['redirectPageId'] ?? null);
        if ($pluginValue !== null) {
            return $pluginValue > 0 ? $pluginValue : null;
        }
        $fallbackValue = $this->integerSetting($this->saveFormSettings($settings)['fallbackRedirectPageId'] ?? null);
        if ($fallbackValue !== null) {
            return $fallbackValue > 0 ? $fallbackValue : null;
        }
        return null;
    }

    /**
     * The mode of the plugin setting, of the TypoScript fallback when the plugin setting
     * names no mode, and {@see FlashMessageCreationMode::default()} when neither does.
     *
     * @param array<string, mixed> $settings
     */
    public function flashMessageCreationMode(array $settings): FlashMessageCreationMode
    {
        return $this->mode($settings['flashMessageCreationMode'] ?? null)
            ?? $this->mode($this->saveFormSettings($settings)['fallbackFlashMessageCreationMode'] ?? null)
            ?? FlashMessageCreationMode::default();
    }

    /**
     * The plugin redirects whenever a redirect page is given, while the flash message
     * follows the mode, which counts a page id of 0 or below as no redirect page. Only
     * a listener of the after-save event can hand in such a page id, and the plugin then
     * does both (ACE-435).
     */
    public function decide(
        int $currentPageId,
        ?int $redirectPageId,
        FlashMessageCreationMode $flashMessageCreationMode,
    ): AfterSaveDecision {
        return new AfterSaveDecision(
            redirectPageId: $redirectPageId,
            createFlashMessage: $flashMessageCreationMode->shouldBeCreated($currentPageId, $redirectPageId),
        );
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<mixed>
     */
    private function saveFormSettings(array $settings): array
    {
        return is_array($settings['saveForm'] ?? null) ? $settings['saveForm'] : [];
    }

    private function mode(mixed $value): ?FlashMessageCreationMode
    {
        $value = $this->integerSetting($value);
        return $value !== null ? FlashMessageCreationMode::tryFrom($value) : null;
    }

    private function integerSetting(mixed $value): ?int
    {
        if ((is_string($value) || is_int($value)) && MathUtility::canBeInterpretedAsInteger($value)) {
            return (int)$value;
        }
        return null;
    }
}
