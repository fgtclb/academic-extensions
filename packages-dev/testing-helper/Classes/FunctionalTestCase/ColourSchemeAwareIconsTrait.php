<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Asserts that an icon identifier a TCA record type resolves is drawn for the backend
 * colour scheme: registered with {@see CurrentColorSvgIconProvider}, inlined in both
 * markups, and drawn in `currentColor` with no colour of its own.
 *
 * The core `SvgIconProvider` renders the default markup - the one `typeicon_classes`
 * reaches - as an `<img>`, which is opaque to CSS. A record icon registered that way
 * keeps the ink of its file whatever the colour scheme says, which is what put dark
 * glyphs on the dark cards of the record list (ACE-523).
 *
 * `IconFactory::getIcon()` never fails on an unknown identifier: it answers with the
 * `default-not-found` placeholder. Asserting the identifier of the answer is therefore
 * part of the check, not decoration.
 *
 * `assertIconIsInTheHouseFormat()` adds the format of the shipped Font Awesome Free set
 * to the checks per identifier. The methods taking an extension key check the icons of
 * the backend registry of a whole extension: the naming scheme of its
 * `Configuration/Icons.php`, the house format of every icon drawn from one of its files,
 * and that every type it owns names an icon of its own. docs/architecture/icons.md
 * states the rules they enforce. {@see FrontendIconsAssertionTrait} has the same checks
 * for the frontend registry, {@see IconFilesAssertionTrait} the checks of the files.
 */
trait ColourSchemeAwareIconsTrait
{
    private function assertIconIsRegisteredWithCurrentColorProvider(string $identifier): void
    {
        $iconRegistry = $this->get(IconRegistry::class);

        self::assertTrue(
            $iconRegistry->isRegistered($identifier),
            sprintf('Icon "%s" is not registered.', $identifier),
        );
        self::assertSame(
            CurrentColorSvgIconProvider::class,
            $iconRegistry->getIconConfigurationByIdentifier($identifier)['provider'] ?? null,
            sprintf('Icon "%s" is not registered with the colour scheme aware provider.', $identifier),
        );
        self::assertSame($identifier, $this->getColourSchemeAwareIcon($identifier)->getIdentifier());
    }

    /**
     * The default markup is the inlined file rather than an `<img>`, and the `inline`
     * alternative is the same string - the provider prepares both from the same source.
     */
    private function assertIconIsInlinedInBothMarkups(string $identifier): void
    {
        $icon = $this->getColourSchemeAwareIcon($identifier);
        $markup = $icon->getMarkup();

        self::assertStringStartsWith('<svg', $markup, sprintf('Icon "%s" is not inlined.', $identifier));
        self::assertStringNotContainsString('<img', $markup);
        self::assertStringContainsString('viewBox', $markup);
        self::assertSame($markup, $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    /**
     * The point of the whole exercise: the shapes follow the surrounding text colour and
     * carry no colour of their own, and the markup carries no `id` - it may appear many
     * times in one document, and a duplicated `id` is invalid HTML.
     */
    private function assertIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $markup = $this->getColourSchemeAwareIcon($identifier)->getMarkup();

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

    private function assertRenderedIconCarriesItsIdentifier(string $identifier): void
    {
        $rendered = $this->getColourSchemeAwareIcon($identifier)->render();

        self::assertStringContainsString('data-identifier="' . $identifier . '"', $rendered);
        self::assertStringNotContainsString('default-not-found', $rendered);
        self::assertStringContainsString('<svg', $rendered);
        self::assertStringNotContainsString('<img', $rendered);
    }

    /**
     * The list based assertions above pin the icons that exist today. This one is derived
     * from the TCA instead, so a record icon added later cannot slip past unconverted - the
     * case a hand maintained list is structurally unable to catch.
     *
     * It walks the `ctrl` of every TCA table and keeps what it can attribute to
     * `$extensionKey`. Three things have to hold.
     *
     * A `ctrl.iconfile` naming a file of this extension is a defect of its own: it bypasses
     * the icon registry, so the file gets whatever `IconRegistry::detectIconProvider()`
     * answers and can never carry a provider of our choosing.
     *
     * A `ctrl.typeicon_classes` value is an icon *identifier*, never a file path. A path is
     * silently accepted there - `ExtensionManagementUtility::addPlugin()` and
     * `TcaManipulator::addRecordType()` both write the `icon` key of a select item straight
     * into it - and `IconRegistry::registerTCAIcons()` registers from `ctrl.iconfile` only,
     * so `IconFactory::getIcon()` never finds it and answers with `default-not-found`
     * (ACE-523).
     *
     * And the identifier uses the colour scheme aware provider, because `typeicon_classes`
     * is read through the *default* markup everywhere it matters.
     *
     * `tt_content` is out of the last check on purpose. Its `typeicon_classes` entries are
     * the content element icons, which are plugin brand marks drawn in fixed colours and
     * meant to look the same on every background. The path check still applies to them.
     */
    private function assertEveryRecordTypeIconIsColourSchemeAware(string $extensionKey): void
    {
        $iconRegistry = $this->get(IconRegistry::class);
        $sourcePrefix = 'EXT:' . $extensionKey . '/';
        $checked = 0;

        foreach ($GLOBALS['TCA'] ?? [] as $table => $tableConfiguration) {
            $iconFile = $tableConfiguration['ctrl']['iconfile'] ?? null;
            if (is_string($iconFile)) {
                self::assertStringNotContainsString(
                    $sourcePrefix,
                    $iconFile,
                    sprintf(
                        '%s.ctrl.iconfile points at "%s" and bypasses the icon registry. '
                        . 'Register the file in Configuration/Icons.php and name the identifier '
                        . 'in ctrl.typeicon_classes instead.',
                        $table,
                        $iconFile,
                    ),
                );
            }

            $typeIconClasses = $tableConfiguration['ctrl']['typeicon_classes'] ?? [];
            if (!is_array($typeIconClasses)) {
                continue;
            }
            foreach ($typeIconClasses as $type => $identifier) {
                if (!is_string($identifier) || $identifier === '') {
                    continue;
                }
                $where = sprintf('%s.ctrl.typeicon_classes.%s', $table, (string)$type);
                if (str_contains($identifier, '/')) {
                    self::assertStringNotContainsString(
                        $sourcePrefix,
                        $identifier,
                        sprintf(
                            '%s names the file "%s" instead of a registered icon identifier, '
                            . 'so the backend renders the not-found placeholder for it.',
                            $where,
                            $identifier,
                        ),
                    );
                    continue;
                }
                if ($table === 'tt_content' || !$iconRegistry->isRegistered($identifier)) {
                    continue;
                }
                $configuration = $iconRegistry->getIconConfigurationByIdentifier($identifier);
                if (!str_starts_with((string)($configuration['options']['source'] ?? ''), $sourcePrefix)) {
                    continue;
                }
                $checked++;
                self::assertSame(
                    CurrentColorSvgIconProvider::class,
                    $configuration['provider'] ?? null,
                    sprintf(
                        'The record icon "%s" of %s is not registered with the colour scheme aware provider.',
                        $identifier,
                        $where,
                    ),
                );
            }
        }

        self::assertGreaterThan(
            0,
            $checked,
            sprintf(
                'No record type icon of EXT:%s was found in the TCA - the walk asserted nothing.',
                $extensionKey,
            ),
        );
    }

    /**
     * The house format of the Font Awesome Free icons the academic extensions ship, as
     * the backend registry renders it: one 640 unit grid for every icon, so a row of
     * icons does not jump in size, `fill="currentColor"` on the root element, no
     * `style` attribute, and `width="1em" height="1em"`.
     *
     * The size is what a frontend page needs, `.icon svg { width: 100%; height: 100% }`
     * exists in the backend stylesheet only. Both core versions keep the two
     * attributes, and the backend overrides them inside `.icon`, so they cost a
     * backend-only icon nothing.
     */
    private function assertIconIsInTheHouseFormat(string $identifier): void
    {
        $markup = $this->getColourSchemeAwareIcon($identifier)->getMarkup();
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
     * Every type an extension owns names an icon of its own, and that icon is drawn
     * for the colour scheme. {@see self::assertEveryRecordTypeIconIsColourSchemeAware()}
     * looks at what it can attribute to an extension by the icon. This one decides by
     * *type* what belongs to the extension, never by what the identifier looks like:
     * every type of a table `tx_<key without underscores>_*`, plus the content element
     * types in `$contentTypes` and the page types in `$pageTypes`, with their
     * `<doktype>-<variant>` entries. Those two are passed in, because `tt_content` and
     * `pages` are shared by every extension and a CType carries no reliable mark of its
     * owner (`academic_study_plan` is one of them).
     *
     * An owned table needs a `default` type icon, and every named type an entry: the
     * backend shows the icon of the table or `default-not-found` otherwise, which is
     * what an empty `icon` of a content element registration leaves behind, because
     * `addPlugin()` and `TcaManipulator::addRecordType()` write no entry for it. Every
     * owned entry is an identifier of the extension in the group of its kind,
     * `tx-<key>-record-` for a table, `-plugin-` for a content element, `-doktype-`
     * for a page type, never a core or a foreign one, and that identifier is
     * registered, not deprecated, on {@see CurrentColorSvgIconProvider}, inlined in
     * both markups and drawn in `currentColor`.
     *
     * What it cannot see is a content element or page type the test does not name, and
     * two types that swapped their icons, which is why the icon tests also pin the
     * identifier each type names.
     *
     * @param list<string> $contentTypes the `tt_content` CTypes the extension registers
     * @param list<int|string> $pageTypes the `pages` doktypes the extension registers
     */
    private function assertEveryTypeOfTheExtensionNamesAnIconOfItsOwn(
        string $extensionKey,
        array $contentTypes = [],
        array $pageTypes = [],
    ): void {
        $keyWithoutUnderscores = str_replace('_', '', $extensionKey);
        $tablePrefix = 'tx_' . $keyWithoutUnderscores . '_';
        $ownedTypes = [];
        foreach ($contentTypes as $contentType) {
            $ownedTypes['tt_content'][(string)$contentType] = true;
        }
        foreach ($pageTypes as $pageType) {
            $ownedTypes['pages'][(string)$pageType] = true;
        }
        $checked = 0;

        foreach ($GLOBALS['TCA'] ?? [] as $table => $tableConfiguration) {
            $table = (string)$table;
            $isOwnedTable = str_starts_with($table, $tablePrefix);
            if (!$isOwnedTable && !isset($ownedTypes[$table])) {
                continue;
            }
            $typeIconClasses = $tableConfiguration['ctrl']['typeicon_classes'] ?? [];
            if (!is_array($typeIconClasses)) {
                $typeIconClasses = [];
            }
            if ($isOwnedTable) {
                self::assertArrayHasKey(
                    'default',
                    $typeIconClasses,
                    sprintf('%s.ctrl.typeicon_classes has no default, so the table has no icon of its own.', $table),
                );
            }
            foreach (array_keys($ownedTypes[$table] ?? []) as $ownedType) {
                self::assertArrayHasKey(
                    $ownedType,
                    $typeIconClasses,
                    sprintf(
                        '%s.ctrl.typeicon_classes has no entry for the type "%s" of EXT:%s, so the backend '
                        . 'shows the default icon of the table for it.',
                        $table,
                        $ownedType,
                        $extensionKey,
                    ),
                );
            }
            $group = match ($table) {
                'tt_content' => 'plugin',
                'pages' => 'doktype',
                default => 'record',
            };
            foreach ($typeIconClasses as $type => $identifier) {
                $type = (string)$type;
                $isOwnedType = $isOwnedTable
                    || isset($ownedTypes[$table][$type])
                    || ($table === 'pages' && isset($ownedTypes[$table][explode('-', $type, 2)[0]]));
                if (!$isOwnedType) {
                    continue;
                }
                $this->assertOwnedTypeIconIsColourSchemeAware(
                    sprintf('%s.ctrl.typeicon_classes.%s', $table, $type),
                    $identifier,
                    sprintf('tx-%s-%s-', $keyWithoutUnderscores, $group),
                );
                $checked++;
            }
        }

        self::assertGreaterThan(
            0,
            $checked,
            sprintf('EXT:%s owns no type in the TCA - the walk asserted nothing.', $extensionKey),
        );
    }

    /**
     * Every identifier the extension registers in its `Configuration/Icons.php`, the
     * file of the backend registry, follows `tx-<key without underscores>-<group>-<name>`
     * in a group of the backend, `record`, `plugin` or `doktype`, is registered with
     * {@see CurrentColorSvgIconProvider}, and draws an existing SVG file in lowercase
     * kebab case below `Resources/Public/Icons/<directory>/`, of the extension itself or
     * of the shared set of `academic_base`, which every academic extension requires. A
     * file of any other extension would be a dependency nobody declared.
     *
     * An `action`, `state` or `info` identifier fails: it belongs in
     * `Configuration/FrontendIcons.php`, the group decides the registry. `$groups`
     * narrows the backend groups to the ones the extension uses, so an extension
     * without a page type cannot gain a `doktype` icon unnoticed.
     *
     * The identifiers are read out of the file rather than the registry, because the
     * registry cannot tell which extension registered what. The extension key is in
     * every identifier because the registry is flat and a duplicate registration wins
     * silently, and it is written without underscores because a dashed key is ambiguous
     * in this family (`academic_persons` with `edit-print` and `academic_persons_edit`
     * with `print` would both read `academic-persons-edit-print`).
     *
     * @param list<string> $groups the backend groups the extension uses
     */
    private function assertIconIdentifiersFollowTheNamingScheme(
        string $extensionKey,
        array $groups = ['record', 'plugin', 'doktype'],
    ): void {
        self::assertSame(
            [],
            array_values(array_diff($groups, ['record', 'plugin', 'doktype'])),
            'Only record, plugin and doktype icons are registered in Configuration/Icons.php.',
        );
        $packagePath = $this->get(PackageManager::class)->getPackage($extensionKey)->getPackagePath();
        $iconsFile = $packagePath . 'Configuration/Icons.php';
        self::assertFileExists($iconsFile, sprintf('EXT:%s ships no Configuration/Icons.php.', $extensionKey));
        $icons = require $iconsFile;
        self::assertIsArray($icons, sprintf('EXT:%s/Configuration/Icons.php does not return an array.', $extensionKey));
        self::assertNotSame([], $icons, sprintf('EXT:%s registers no icon - the check asserted nothing.', $extensionKey));

        $identifierPattern = sprintf(
            '/^tx-%s-(action|state|info|record|plugin|doktype)-([a-z0-9]+(-[a-z0-9]+)*)$/',
            preg_quote(str_replace('_', '', $extensionKey), '/'),
        );
        $sourcePattern = sprintf(
            '#^EXT:(%s|academic_base)/(Resources/Public/Icons/[a-z0-9]+(-[a-z0-9]+)*/[a-z0-9]+(-[a-z0-9]+)*\.svg)$#',
            preg_quote($extensionKey, '#'),
        );
        foreach ($icons as $identifier => $configuration) {
            $identifier = (string)$identifier;
            self::assertSame(
                1,
                preg_match($identifierPattern, $identifier, $identifierParts),
                sprintf('The icon identifier "%s" of EXT:%s does not follow the naming scheme.', $identifier, $extensionKey),
            );
            self::assertNotContains(
                $identifierParts[1],
                ['action', 'state', 'info'],
                sprintf(
                    'Icon "%s" is an %s icon and belongs in Configuration/FrontendIcons.php, not in '
                    . 'Configuration/Icons.php of EXT:%s.',
                    $identifier,
                    $identifierParts[1],
                    $extensionKey,
                ),
            );
            self::assertContains(
                $identifierParts[1],
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
            self::assertSame(
                1,
                preg_match($sourcePattern, $source, $matches),
                sprintf(
                    'The source "%s" of icon "%s" is not an SVG file below Resources/Public/Icons/<directory>/ '
                    . 'of EXT:%s or EXT:academic_base.',
                    $source,
                    $identifier,
                    $extensionKey,
                ),
            );
            self::assertFileExists(
                $this->get(PackageManager::class)->getPackage($matches[1])->getPackagePath() . $matches[2],
                sprintf('The source "%s" of icon "%s" does not exist.', $source, $identifier),
            );
        }
    }

    /**
     * Every icon of the backend registry that is drawn from a file of the extension, or
     * that the extension registers in its `Configuration/Icons.php`, is registered with
     * {@see CurrentColorSvgIconProvider} and in the house format, see
     * {@see self::assertIconIsInTheHouseFormat()}.
     *
     * It walks the registry rather than the file, so it also reaches the icons
     * `EXT:category_types` registers from a `Configuration/CategoryTypes.yaml`, the
     * type icons `category_types.<group>.<type>` and the group icons
     * `category_types_group.<group>`, and for `academic_base` every icon another
     * extension draws from a file of the shared set. A deprecated identifier is skipped
     * before its configuration is read, because reading it raises `E_USER_DEPRECATED`,
     * and no icon of the academic extensions is deprecated.
     */
    private function assertEveryIconOfTheExtensionIsInTheHouseFormat(string $extensionKey): void
    {
        $iconRegistry = $this->get(IconRegistry::class);
        $packagePath = $this->get(PackageManager::class)->getPackage($extensionKey)->getPackagePath();
        $declared = is_file($packagePath . 'Configuration/Icons.php') ? require $packagePath . 'Configuration/Icons.php' : [];
        self::assertIsArray($declared, sprintf('EXT:%s/Configuration/Icons.php does not return an array.', $extensionKey));
        $sourcePrefix = 'EXT:' . $extensionKey . '/Resources/Public/Icons/';
        $checked = 0;

        foreach ($iconRegistry->getAllRegisteredIconIdentifiers() as $identifier) {
            $identifier = (string)$identifier;
            if ($iconRegistry->isDeprecated($identifier)) {
                continue;
            }
            $configuration = $iconRegistry->getIconConfigurationByIdentifier($identifier);
            $source = (string)($configuration['options']['source'] ?? '');
            if (!str_starts_with($source, $sourcePrefix) && !array_key_exists($identifier, $declared)) {
                continue;
            }
            self::assertSame(
                CurrentColorSvgIconProvider::class,
                $configuration['provider'] ?? null,
                sprintf('Icon "%s" (%s) is not registered with the colour scheme aware provider.', $identifier, $source),
            );
            $this->assertIconIsInTheHouseFormat($identifier);
            $checked++;
        }

        self::assertGreaterThan(
            0,
            $checked,
            sprintf('No icon of the backend registry is drawn from a file of EXT:%s - the walk asserted nothing.', $extensionKey),
        );
    }

    /**
     * The checks for the icon of a type the extension owns, see
     * {@see self::assertEveryTypeOfTheExtensionNamesAnIconOfItsOwn()}.
     */
    private function assertOwnedTypeIconIsColourSchemeAware(string $where, mixed $identifier, string $identifierPrefix): void
    {
        self::assertIsString($identifier, sprintf('%s is not an icon identifier.', $where));
        self::assertNotSame('', $identifier, sprintf('%s is empty, so the type has no icon.', $where));
        self::assertStringNotContainsString(
            '/',
            $identifier,
            sprintf(
                '%s names the file "%s" instead of a registered icon identifier, so the backend renders the '
                . 'not-found placeholder for it.',
                $where,
                $identifier,
            ),
        );
        self::assertStringStartsWith(
            $identifierPrefix,
            $identifier,
            sprintf(
                '%s names "%s". A type of this extension names an icon of its own, %s<name>, never a core or a '
                . 'foreign one.',
                $where,
                $identifier,
                $identifierPrefix,
            ),
        );
        $iconRegistry = $this->get(IconRegistry::class);
        self::assertTrue(
            $iconRegistry->isRegistered($identifier),
            sprintf(
                '%s names "%s", which is not registered, so the backend renders the not-found placeholder for it.',
                $where,
                $identifier,
            ),
        );
        self::assertFalse(
            $iconRegistry->isDeprecated($identifier),
            sprintf('%s names "%s", which is deprecated.', $where, $identifier),
        );
        self::assertSame(
            CurrentColorSvgIconProvider::class,
            $iconRegistry->getIconConfigurationByIdentifier($identifier)['provider'] ?? null,
            sprintf('The icon "%s" of %s is not registered with the colour scheme aware provider.', $identifier, $where),
        );
        $this->assertIconIsInlinedInBothMarkups($identifier);
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    private function getColourSchemeAwareIcon(string $identifier): Icon
    {
        return $this->get(IconFactory::class)->getIcon($identifier, IconSize::SMALL);
    }
}
