<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Form;

use FGTCLB\AcademicBase\Form\AfterSaveResolver;
use FGTCLB\AcademicBase\Form\FlashMessageCreationMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The plugin settings an integrator configures for the page after a save, and the
 * decision a form plugin takes from them.
 *
 * The settings reach the resolver as Extbase hands them to a controller: the FlexForm
 * values of the content element as strings, merged over the TypoScript settings, whose
 * values are strings as well. An integer is what a listener or a test passes.
 */
final class AfterSaveResolverTest extends UnitTestCase
{
    /**
     * @param array<string, mixed> $settings
     */
    #[Test]
    #[DataProvider('redirectPageSettings')]
    public function theRedirectPageComesFromThePluginFirstAndFromTypoScriptOtherwise(array $settings, ?int $expected): void
    {
        $this->assertSame($expected, (new AfterSaveResolver())->redirectPageId($settings));
    }

    /**
     * @return \Generator<string, array{0: array<string, mixed>, 1: int|null}>
     */
    public static function redirectPageSettings(): \Generator
    {
        yield 'nothing configured' => [[], null];
        yield 'plugin page' => [['redirectPageId' => '12'], 12];
        yield 'plugin page as integer' => [['redirectPageId' => 12], 12];
        yield 'plugin page wins over the fallback' => [['redirectPageId' => '12', 'saveForm' => ['fallbackRedirectPageId' => '20']], 12];
        yield 'fallback page' => [['saveForm' => ['fallbackRedirectPageId' => '20']], 20];
        yield 'empty plugin value uses the fallback' => [['redirectPageId' => '', 'saveForm' => ['fallbackRedirectPageId' => '20']], 20];
        yield 'non numeric plugin value uses the fallback' => [['redirectPageId' => 'list', 'saveForm' => ['fallbackRedirectPageId' => '20']], 20];
        yield 'array plugin value uses the fallback' => [['redirectPageId' => ['12'], 'saveForm' => ['fallbackRedirectPageId' => '20']], 20];
        // An integer in the plugin setting decides alone, even when it is no page id.
        yield 'plugin page zero does not fall back' => [['redirectPageId' => '0', 'saveForm' => ['fallbackRedirectPageId' => '20']], null];
        yield 'negative plugin page does not fall back' => [['redirectPageId' => '-3', 'saveForm' => ['fallbackRedirectPageId' => '20']], null];
        yield 'fallback page zero' => [['saveForm' => ['fallbackRedirectPageId' => '0']], null];
        yield 'negative fallback page' => [['saveForm' => ['fallbackRedirectPageId' => '-3']], null];
        yield 'non numeric fallback page' => [['saveForm' => ['fallbackRedirectPageId' => 'list']], null];
        yield 'save form settings that are no array' => [['saveForm' => '20'], null];
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[Test]
    #[DataProvider('flashMessageCreationModeSettings')]
    public function theModeComesFromThePluginFirstAndFromTypoScriptOtherwise(array $settings, FlashMessageCreationMode $expected): void
    {
        $this->assertSame($expected, (new AfterSaveResolver())->flashMessageCreationMode($settings));
    }

    /**
     * @return \Generator<string, array{0: array<string, mixed>, 1: FlashMessageCreationMode}>
     */
    public static function flashMessageCreationModeSettings(): \Generator
    {
        yield 'nothing configured' => [[], FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE];
        yield 'plugin mode always' => [['flashMessageCreationMode' => '1'], FlashMessageCreationMode::ALWAYS];
        yield 'plugin mode never' => [['flashMessageCreationMode' => '2'], FlashMessageCreationMode::NEVER];
        yield 'plugin mode as integer' => [['flashMessageCreationMode' => 2], FlashMessageCreationMode::NEVER];
        yield 'plugin mode zero wins over the fallback' => [['flashMessageCreationMode' => '0', 'saveForm' => ['fallbackFlashMessageCreationMode' => '2']], FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE];
        yield 'fallback mode' => [['saveForm' => ['fallbackFlashMessageCreationMode' => '1']], FlashMessageCreationMode::ALWAYS];
        // Unlike the redirect page, a value that names no mode falls back.
        yield 'empty plugin mode uses the fallback' => [['flashMessageCreationMode' => '', 'saveForm' => ['fallbackFlashMessageCreationMode' => '2']], FlashMessageCreationMode::NEVER];
        yield 'unknown plugin mode uses the fallback' => [['flashMessageCreationMode' => '3', 'saveForm' => ['fallbackFlashMessageCreationMode' => '2']], FlashMessageCreationMode::NEVER];
        yield 'negative plugin mode uses the fallback' => [['flashMessageCreationMode' => '-1', 'saveForm' => ['fallbackFlashMessageCreationMode' => '2']], FlashMessageCreationMode::NEVER];
        yield 'unknown fallback mode' => [['saveForm' => ['fallbackFlashMessageCreationMode' => '7']], FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE];
        yield 'non numeric fallback mode' => [['saveForm' => ['fallbackFlashMessageCreationMode' => 'never']], FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE];
        yield 'save form settings that are no array' => [['saveForm' => '2'], FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE];
    }

    #[Test]
    #[DataProvider('decisions')]
    public function theDecisionRedirectsToTheGivenPageAndFollowsTheModeForTheMessage(
        FlashMessageCreationMode $mode,
        ?int $redirectPageId,
        ?int $expectedRedirectPageId,
        bool $expectedFlashMessage,
    ): void {
        $decision = (new AfterSaveResolver())->decide(10, $redirectPageId, $mode);

        $this->assertSame($expectedRedirectPageId, $decision->redirectPageId);
        $this->assertSame($expectedFlashMessage, $decision->createFlashMessage);
    }

    /**
     * @return \Generator<string, array{0: FlashMessageCreationMode, 1: int|null, 2: int|null, 3: bool}>
     */
    public static function decisions(): \Generator
    {
        foreach (FlashMessageCreationMode::cases() as $mode) {
            $stays = $mode !== FlashMessageCreationMode::NEVER;
            yield $mode->name . ', no redirect page' => [$mode, null, null, $stays];
            yield $mode->name . ', redirect to the current page' => [$mode, 10, 10, $stays];
            yield $mode->name . ', redirect to another page' => [$mode, 11, 11, $mode === FlashMessageCreationMode::ALWAYS];
            // A listener may hand in a page id of 0. The plugin redirects to it, while the
            // mode counts it as no redirect page (ACE-435).
            yield $mode->name . ', redirect page zero' => [$mode, 0, 0, $stays];
        }
    }
}
