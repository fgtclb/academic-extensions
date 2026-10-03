<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use FGTCLB\AcademicBase\Imaging\FrontendIcon;
use FGTCLB\AcademicBase\Imaging\FrontendIconFactory;
use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * Asserts the registrations of the frontend icon registry of academic_base, and how
 * an icon of it relates to the icon registry of the TYPO3 backend.
 *
 * The frontend registry reads `Configuration/FrontendIcons.php`, the backend registry
 * `Configuration/Icons.php`, and neither reads the other. An icon a frontend template
 * renders belongs in the first, an icon the backend renders in the second, and an icon
 * both render in both, with the same configuration.
 *
 * {@see FrontendIconFactory::getIcon()} never fails on an unknown identifier: it
 * answers with the `default-not-found` placeholder. Asserting the identifier of the
 * answer is therefore part of the check, not decoration.
 */
trait FrontendIconsAssertionTrait
{
    /**
     * @param class-string $providerClass
     */
    private function assertFrontendIconIsRegisteredWithProvider(string $identifier, string $providerClass): void
    {
        $frontendIconRegistry = $this->get(FrontendIconRegistry::class);

        self::assertTrue(
            $frontendIconRegistry->isRegistered($identifier),
            sprintf('Icon "%s" is not registered in the frontend icon registry.', $identifier),
        );
        self::assertSame(
            $providerClass,
            $frontendIconRegistry->getIconConfiguration($identifier)['provider'] ?? null,
            sprintf('Icon "%s" is not registered in the frontend icon registry with %s.', $identifier, $providerClass),
        );
    }

    /**
     * For an icon that moved to the frontend registry: the backend registry has to have
     * forgotten it, or a site that replaces it in `Configuration/Icons.php` still sees
     * its own drawing in the backend and the shipped one in the frontend.
     */
    private function assertFrontendIconIsNotABackendIcon(string $identifier): void
    {
        self::assertTrue(
            $this->get(FrontendIconRegistry::class)->isRegistered($identifier),
            sprintf('Icon "%s" is not registered in the frontend icon registry.', $identifier),
        );
        self::assertFalse(
            $this->get(IconRegistry::class)->isRegistered($identifier),
            sprintf('Icon "%s" is still registered in the icon registry of the backend.', $identifier),
        );
    }

    /**
     * For an icon the frontend and the backend both render: the same provider and the
     * same options in both files, so both contexts show the same drawing.
     */
    private function assertIconIsRegisteredInBothRegistries(string $identifier): void
    {
        $frontendIconRegistry = $this->get(FrontendIconRegistry::class);
        $iconRegistry = $this->get(IconRegistry::class);

        self::assertTrue(
            $frontendIconRegistry->isRegistered($identifier),
            sprintf('Icon "%s" is not registered in the frontend icon registry.', $identifier),
        );
        self::assertTrue(
            $iconRegistry->isRegistered($identifier),
            sprintf('Icon "%s" is not registered in the icon registry of the backend.', $identifier),
        );
        $backendConfiguration = $iconRegistry->getIconConfigurationByIdentifier($identifier);
        self::assertSame(
            [
                'provider' => $backendConfiguration['provider'] ?? null,
                'options' => $backendConfiguration['options'] ?? [],
            ],
            $frontendIconRegistry->getIconConfiguration($identifier),
            sprintf('Icon "%s" is registered differently in the two registries.', $identifier),
        );
    }

    /**
     * The shapes follow the surrounding text colour and carry no colour of their own, and
     * the markup carries no `id` and no `<style>`, the checks of
     * {@see ColourSchemeAwareIconsTrait::assertIconMarkupFollowsTheTextColour()} for an
     * icon of the frontend registry.
     */
    private function assertFrontendIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $markup = $this->getFrontendIcon($identifier)->getMarkup();

        self::assertStringContainsString(
            'currentColor',
            $markup,
            sprintf('Icon "%s" is not drawn in currentColor.', $identifier),
        );
        self::assertDoesNotMatchRegularExpression(
            '/#[0-9A-Fa-f]{3,8}\b/',
            $markup,
            sprintf('Icon "%s" carries a hardcoded colour.', $identifier),
        );
        self::assertDoesNotMatchRegularExpression(
            '/<style[\s>]/',
            $markup,
            sprintf('Icon "%s" carries a <style> element.', $identifier),
        );
        self::assertDoesNotMatchRegularExpression(
            '/\sid="/',
            $markup,
            sprintf('Icon "%s" carries an id attribute.', $identifier),
        );
    }

    private function assertRenderedFrontendIconCarriesItsIdentifier(string $identifier): void
    {
        $rendered = $this->getFrontendIcon($identifier)->render();

        self::assertStringContainsString(
            'data-identifier="' . $identifier . '"',
            $rendered,
            sprintf('Icon "%s" does not render under its own identifier.', $identifier),
        );
        self::assertStringNotContainsString('default-not-found', $rendered);
    }

    private function getFrontendIcon(string $identifier): FrontendIcon
    {
        return $this->get(FrontendIconFactory::class)->getIcon($identifier, IconSize::SMALL);
    }
}
