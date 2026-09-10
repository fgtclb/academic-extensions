<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicPersonsEdit\Tests\Functional\AbstractAcademicPersonsEditTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * Renders the icons of the profile editing plugin through the `IconFactory` of the container,
 * the way `<core:icon>` and the backend do.
 *
 * `IconFactory::getIcon()` never fails on an unknown identifier: it answers with the
 * `default-not-found` placeholder, so a typo in a registration, a renamed file or a deleted
 * one reaches a page as a small red icon and nothing else. The lists below are spelled out
 * here rather than read back out of a `Configuration/Icons.php`, so a rename has to be made
 * twice - in the registration and here - instead of silently agreeing with itself.
 */
final class ProfileEditingIconsTest extends AbstractAcademicPersonsEditTestCase
{
    use ColourSchemeAwareIconsTrait;

    private const PLUGIN_ICON_IDENTIFIER = 'tx-academicpersonsedit-plugin-profile-editing';
    private const PROFILE_EDITING_CONTENT_TYPE = 'academicpersonsedit_profileediting';

    /**
     * The action and state icons of the editing frontend. They are registered by
     * EXT:academic_base, not by this extension, so these tests pin the part of that API the
     * templates depend on.
     *
     * @return \Generator<string, array{0: string}>
     */
    public static function actionIconIdentifiers(): \Generator
    {
        $identifiers = [
            'tx-academicbase-action-add',
            'tx-academicbase-action-back',
            'tx-academicbase-action-clear',
            'tx-academicbase-action-delete',
            'tx-academicbase-action-drag',
            'tx-academicbase-action-edit',
            'tx-academicbase-action-help',
            'tx-academicbase-action-move-down',
            'tx-academicbase-action-move-up',
            'tx-academicbase-action-save',
            'tx-academicbase-action-undo',
            'tx-academicbase-action-upload-image',
            'tx-academicbase-action-view',
            'tx-academicbase-action-view-close',
            'tx-academicbase-state-visible',
            'tx-academicbase-state-hidden',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    #[Test]
    #[DataProvider('actionIconIdentifiers')]
    public function actionIconResolves(string $identifier): void
    {
        $this->assertTrue($this->get(IconRegistry::class)->isRegistered($identifier));
        $this->assertSame($identifier, $this->getIcon($identifier)->getIdentifier());
    }

    /**
     * The default markup is the inlined file, not an `<img>`. That is the whole reason the
     * set is registered with {@see CurrentColorSvgIconProvider}: an `<img>` keeps the colours
     * of its file, an inlined `<svg>` drawn in `currentColor` takes the colour of the button
     * it sits in - in the frontend as much as in a dark backend colour scheme.
     */
    #[Test]
    #[DataProvider('actionIconIdentifiers')]
    public function actionIconIsInlinedInBothMarkups(string $identifier): void
    {
        $icon = $this->getIcon($identifier);
        $markup = $icon->getMarkup();

        $this->assertStringStartsWith('<svg', $markup);
        $this->assertStringNotContainsString('<img', $markup);
        $this->assertStringContainsString('fill="currentColor"', $markup);
        $this->assertSame($markup, $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    #[Test]
    #[DataProvider('actionIconIdentifiers')]
    public function renderedActionIconCarriesItsIdentifier(string $identifier): void
    {
        $rendered = $this->getIcon($identifier)->render();

        $this->assertStringContainsString('data-identifier="' . $identifier . '"', $rendered);
        $this->assertStringNotContainsString('default-not-found', $rendered);
        $this->assertStringContainsString('<svg', $rendered);
    }

    /**
     * The icon of the content element reaches the page module and the new content element
     * wizard, and follows the backend colour scheme in both.
     */
    #[Test]
    public function pluginIconIsRegisteredWithTheColourSchemeAwareProvider(): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider(self::PLUGIN_ICON_IDENTIFIER);
        $this->assertIconIsInlinedInBothMarkups(self::PLUGIN_ICON_IDENTIFIER);
        $this->assertIconMarkupFollowsTheTextColour(self::PLUGIN_ICON_IDENTIFIER);
        $this->assertRenderedIconCarriesItsIdentifier(self::PLUGIN_ICON_IDENTIFIER);
    }

    /**
     * `TcaManipulator::addContentElementPlugin()` writes the item icon verbatim into
     * `typeicon_classes`, which is what the page module renders for a record of the type.
     */
    #[Test]
    public function contentElementTypeUsesThePluginIcon(): void
    {
        $this->assertSame(
            self::PLUGIN_ICON_IDENTIFIER,
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][self::PROFILE_EDITING_CONTENT_TYPE] ?? null,
        );
        $itemIcons = [];
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [] as $item) {
            if (($item['value'] ?? null) === self::PROFILE_EDITING_CONTENT_TYPE) {
                $itemIcons[] = $item['icon'] ?? null;
            }
        }
        $this->assertSame([self::PLUGIN_ICON_IDENTIFIER], $itemIcons);
    }

    /**
     * Nothing but the content element icon is registered by this extension: the action and
     * state icons are the shared ones, and an identifier of its own that nothing renders
     * would be dead API.
     */
    #[Test]
    public function thisExtensionRegistersOnlyThePluginIcon(): void
    {
        $registeredIcons = require __DIR__ . '/../../../Configuration/Icons.php';
        $this->assertIsArray($registeredIcons);
        $this->assertSame([self::PLUGIN_ICON_IDENTIFIER], array_keys($registeredIcons));
    }

    private function getIcon(string $identifier): Icon
    {
        return $this->get(IconFactory::class)->getIcon($identifier, IconSize::SMALL);
    }
}
