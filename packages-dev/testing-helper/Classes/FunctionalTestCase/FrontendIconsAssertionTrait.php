<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use FGTCLB\AcademicBase\Imaging\FrontendIcon;
use FGTCLB\AcademicBase\Imaging\FrontendIconFactory;
use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Package\PackageManager;

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
 *
 * The methods taking an extension key check the frontend icons of a whole extension:
 * the naming scheme of its `Configuration/FrontendIcons.php` and the house format of
 * every frontend icon drawn from one of its files. docs/architecture/icons.md states
 * the rules they enforce, {@see ColourSchemeAwareIconsTrait} has the same checks for
 * the backend registry and {@see IconFilesAssertionTrait} the checks of the files.
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

    /**
     * The house format of the Font Awesome Free icons the academic extensions ship, as
     * the frontend registry renders it: one 640 unit grid for every icon, so a row of
     * icons does not jump in size, `width="1em" height="1em"`, so the icon follows the
     * font size on a page without a stylesheet sizing `.icon svg`, which a frontend
     * page does not have by default, `fill="currentColor"` on the root element, and no
     * `style` attribute. The same checks as
     * {@see ColourSchemeAwareIconsTrait::assertIconIsInTheHouseFormat()}, for an icon
     * of the frontend registry.
     */
    private function assertFrontendIconIsInTheHouseFormat(string $identifier): void
    {
        $markup = $this->getFrontendIcon($identifier)->getMarkup();
        self::assertSame(
            1,
            preg_match('/^<svg\b[^>]*>/', $markup, $matches),
            sprintf('Icon "%s" is not inlined: %s', $identifier, $markup),
        );
        foreach (['viewBox="0 0 640 640"', 'width="1em"', 'height="1em"', 'fill="currentColor"'] as $attribute) {
            self::assertStringContainsString(
                ' ' . $attribute,
                $matches[0],
                sprintf('The root element of icon "%s" does not carry %s: %s', $identifier, $attribute, $matches[0]),
            );
        }
        self::assertDoesNotMatchRegularExpression(
            '/\sstyle="/',
            $markup,
            sprintf('Icon "%s" carries a style attribute.', $identifier),
        );
    }

    /**
     * Every identifier the extension registers in its `Configuration/FrontendIcons.php`
     * follows `tx-<key without underscores>-<group>-<name>` in a group of the frontend,
     * `action`, `state` or `info`, and is registered with
     * {@see CurrentColorSvgIconProvider}. A `record`, `plugin` or `doktype` identifier
     * fails: it belongs in `Configuration/Icons.php`, the group decides the registry.
     * `$groups` narrows the frontend groups to the ones the extension uses.
     *
     * The file is either the extension's own, named after the identifier,
     * `EXT:<key>/Resources/Public/Icons/<group>/<name>.svg`, or a file of the shared
     * set of `academic_base`, `Icons/action|state|info/<file>.svg`, which every
     * academic extension requires. Either has to exist.
     *
     * `default-not-found` of `academic_base` is exempt. It is the placeholder an
     * unknown identifier renders, the drawing and the identifier of TYPO3, with the
     * provider of TYPO3, and a site package replaces it under that name.
     *
     * The identifiers are read out of the file rather than the registry, because the
     * registry cannot tell which package registered what.
     *
     * @param list<string> $groups the frontend groups the extension uses
     */
    private function assertFrontendIconIdentifiersFollowTheNamingScheme(
        string $extensionKey,
        array $groups = ['action', 'state', 'info'],
    ): void {
        self::assertSame(
            [],
            array_values(array_diff($groups, ['action', 'state', 'info'])),
            'Only action, state and info icons are registered in Configuration/FrontendIcons.php.',
        );
        $icons = $this->getIconsDeclaredInFrontendIconsFile($extensionKey);
        if ($extensionKey === 'academic_base') {
            unset($icons['default-not-found']);
        }
        self::assertNotSame([], $icons, sprintf('EXT:%s registers no frontend icon - the check asserted nothing.', $extensionKey));

        $keyWithoutUnderscores = str_replace('_', '', $extensionKey);
        $identifierPattern = sprintf(
            '/^tx-%s-(action|state|info|record|plugin|doktype)-([a-z0-9]+(-[a-z0-9]+)*)$/',
            preg_quote($keyWithoutUnderscores, '/'),
        );
        $sharedSourcePattern = '#^EXT:academic_base/(Resources/Public/Icons/(action|state|info)/[a-z0-9]+(-[a-z0-9]+)*\.svg)$#';
        foreach ($icons as $identifier => $configuration) {
            $identifier = (string)$identifier;
            self::assertSame(
                1,
                preg_match($identifierPattern, $identifier, $identifierParts),
                sprintf('The icon identifier "%s" of EXT:%s does not follow the naming scheme.', $identifier, $extensionKey),
            );
            [, $group, $name] = $identifierParts;
            self::assertContains(
                $group,
                ['action', 'state', 'info'],
                sprintf(
                    'Icon "%s" is a %s icon and belongs in Configuration/Icons.php, not in '
                    . 'Configuration/FrontendIcons.php of EXT:%s.',
                    $identifier,
                    $group,
                    $extensionKey,
                ),
            );
            self::assertContains(
                $group,
                $groups,
                sprintf('Icon "%s" is in a group EXT:%s does not use.', $identifier, $extensionKey),
            );
            self::assertIsArray($configuration, sprintf('Icon "%s" is not configured as an array.', $identifier));
            self::assertSame(
                CurrentColorSvgIconProvider::class,
                $configuration['provider'] ?? null,
                sprintf('Icon "%s" is not registered with the colour scheme aware provider.', $identifier),
            );
            $source = (string)($configuration['source'] ?? '');
            $ownFile = sprintf('Resources/Public/Icons/%s/%s.svg', $group, $name);
            if ($source === sprintf('EXT:%s/%s', $extensionKey, $ownFile)) {
                $file = $this->get(PackageManager::class)->getPackage($extensionKey)->getPackagePath() . $ownFile;
            } else {
                self::assertSame(
                    1,
                    preg_match($sharedSourcePattern, $source, $sourceParts),
                    sprintf(
                        'The source "%s" of icon "%s" is neither EXT:%s/%s nor a file of the shared set of '
                        . 'EXT:academic_base.',
                        $source,
                        $identifier,
                        $extensionKey,
                        $ownFile,
                    ),
                );
                $file = $this->get(PackageManager::class)->getPackage('academic_base')->getPackagePath() . $sourceParts[1];
            }
            self::assertFileExists($file, sprintf('The source "%s" of icon "%s" does not exist.', $source, $identifier));
        }
    }

    /**
     * Every icon of the frontend registry that is drawn from a file of the extension, or
     * that the extension registers in its `Configuration/FrontendIcons.php`, is
     * registered with {@see CurrentColorSvgIconProvider} and in the house format, see
     * {@see self::assertFrontendIconIsInTheHouseFormat()}.
     *
     * It walks the registry rather than the file, so it also reaches the category type
     * and group icons `EXT:category_types` contributes for a
     * `Configuration/CategoryTypes.yaml`, and for `academic_base` every icon another
     * extension draws from a file of the shared set. `default-not-found` of
     * `academic_base` is exempt, see
     * {@see self::assertFrontendIconIdentifiersFollowTheNamingScheme()}.
     */
    private function assertEveryFrontendIconOfTheExtensionIsInTheHouseFormat(string $extensionKey): void
    {
        $frontendIconRegistry = $this->get(FrontendIconRegistry::class);
        $declared = $this->getIconsDeclaredInFrontendIconsFile($extensionKey);
        $sourcePrefix = 'EXT:' . $extensionKey . '/Resources/Public/Icons/';
        $checked = 0;

        foreach ($frontendIconRegistry->getAllRegisteredIconIdentifiers() as $identifier) {
            if ($identifier === 'default-not-found') {
                continue;
            }
            $configuration = $frontendIconRegistry->getIconConfiguration($identifier);
            $source = (string)($configuration['options']['source'] ?? '');
            if (!str_starts_with($source, $sourcePrefix) && !array_key_exists($identifier, $declared)) {
                continue;
            }
            self::assertSame(
                CurrentColorSvgIconProvider::class,
                $configuration['provider'] ?? null,
                sprintf('Icon "%s" (%s) is not registered with the colour scheme aware provider.', $identifier, $source),
            );
            $this->assertFrontendIconIsInTheHouseFormat($identifier);
            $checked++;
        }

        self::assertGreaterThan(
            0,
            $checked,
            sprintf('No icon of the frontend registry is drawn from a file of EXT:%s - the walk asserted nothing.', $extensionKey),
        );
    }

    /**
     * @return array<array-key, mixed> the array the file returns, `[]` without one
     */
    private function getIconsDeclaredInFrontendIconsFile(string $extensionKey): array
    {
        $file = $this->get(PackageManager::class)->getPackage($extensionKey)->getPackagePath()
            . 'Configuration/FrontendIcons.php';
        if (!is_file($file)) {
            return [];
        }
        $icons = require $file;
        self::assertIsArray($icons, sprintf('EXT:%s/Configuration/FrontendIcons.php does not return an array.', $extensionKey));

        return $icons;
    }

    private function getFrontendIcon(string $identifier): FrontendIcon
    {
        return $this->get(FrontendIconFactory::class)->getIcon($identifier, IconSize::SMALL);
    }
}
