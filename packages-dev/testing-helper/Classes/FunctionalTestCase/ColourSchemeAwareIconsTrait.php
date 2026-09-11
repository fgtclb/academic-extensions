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
 * `assertIconIsInTheHouseFormat()` adds the format of the shipped Font Awesome Free set to
 * the per-identifier checks. The methods taking an extension key check a whole extension:
 * the `tx-<key>-<group>-<name>` naming scheme, that no icon file is left unregistered, and
 * that every file is attributed in the licence notice. docs/architecture/icons.md states
 * the rules they enforce.
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
     * from the TCA instead, so a record type added later cannot slip past unconverted - the
     * case a hand maintained list is structurally unable to catch - and neither can a content
     * element or page type the test names.
     *
     * It decides by *type* what belongs to the extension, never by what the icon identifier
     * looks like: every type of a table `tx_<key without underscores>_*`, plus the content
     * element types and page types the test names in `$contentTypes` and `$pageTypes`. Those
     * two have to be named, because `tt_content` and `pages` are shared by every extension
     * and a CType carries no reliable mark of its owner (`academic_study_plan` is one of
     * them). For a page type, the `<doktype>-<variant>` entries (`-hideinmenu`, `-root`, ...)
     * belong to it too. Every type the extension owns names an identifier - one of its own,
     * `tx-<key without underscores>-...`, never a core or a foreign one - and that identifier
     * is registered, not deprecated, and drawn for the colour scheme: registered with
     * {@see CurrentColorSvgIconProvider}, inlined in both markups, in `currentColor`. An
     * owned table without a `default` type icon, and a named CType or doktype without an
     * entry at all - `TcaManipulator::addRecordType()` writes none for an empty `icon` -
     * fail as well, because the backend then shows the icon of the table or
     * `default-not-found`.
     *
     * A type the extension does not own is looked at only when its identifier is
     * attributable to the extension anyway: by the prefix, or by a registered source below
     * `EXT:<key>/`, which is how the category type icons of `sys_category` are covered.
     * Such an identifier has to be registered with the colour scheme aware provider as
     * well. A deprecated identifier on a type of somebody else is skipped before its
     * configuration is read: reading it raises `E_USER_DEPRECATED`, which fails the suite,
     * and it is not this extension's to fix.
     *
     * A `ctrl.iconfile` naming a file of this extension is a defect of its own: it bypasses
     * the icon registry, so the file gets whatever `IconRegistry::detectIconProvider()`
     * answers and can never carry a provider of our choosing. A file path in
     * `ctrl.typeicon_classes` is one as well: `ExtensionManagementUtility::addPlugin()` and
     * `TcaManipulator::addRecordType()` both write the `icon` key of a select item straight
     * into it, `IconRegistry::registerTCAIcons()` registers from `ctrl.iconfile` only, and
     * `IconFactory::getIcon()` answers such a value with `default-not-found` (ACE-523).
     *
     * What it cannot see is a content element or page type the test does not name, whose
     * identifier carries neither the prefix nor a source of the extension, and two types
     * that swapped their icons - which is why the tests also pin the identifier each type
     * names.
     *
     * @param list<string> $contentTypes the `tt_content` CTypes the extension registers
     * @param list<int|string> $pageTypes the `pages` doktypes the extension registers
     */
    private function assertEveryRecordTypeIconIsColourSchemeAware(
        string $extensionKey,
        array $contentTypes = [],
        array $pageTypes = [],
    ): void {
        $iconRegistry = $this->get(IconRegistry::class);
        $sourcePrefix = 'EXT:' . $extensionKey . '/';
        $identifierPrefix = 'tx-' . str_replace('_', '', $extensionKey) . '-';
        $tablePrefix = 'tx_' . str_replace('_', '', $extensionKey) . '_';
        $checked = 0;

        $ownedTypes = [];
        foreach ($contentTypes as $contentType) {
            $ownedTypes['tt_content'][(string)$contentType] = true;
        }
        foreach ($pageTypes as $pageType) {
            $ownedTypes['pages'][(string)$pageType] = true;
        }

        foreach ($GLOBALS['TCA'] ?? [] as $table => $tableConfiguration) {
            $table = (string)$table;
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

            $isOwnedTable = str_starts_with($table, $tablePrefix);
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
                        '%s.ctrl.typeicon_classes has no entry for the type "%s" of EXT:%s, so the '
                        . 'backend shows the default icon of the table for it.',
                        $table,
                        $ownedType,
                        $extensionKey,
                    ),
                );
            }

            foreach ($typeIconClasses as $type => $identifier) {
                $type = (string)$type;
                $where = sprintf('%s.ctrl.typeicon_classes.%s', $table, $type);
                $isOwnedType = $isOwnedTable
                    || isset($ownedTypes[$table][$type])
                    || ($table === 'pages' && isset($ownedTypes[$table][explode('-', $type, 2)[0]]));
                if ($isOwnedType) {
                    $this->assertOwnedTypeIconIsColourSchemeAware($where, $identifier, $identifierPrefix);
                    $checked++;
                    continue;
                }

                if (!is_string($identifier) || $identifier === '') {
                    continue;
                }
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
                $hasPrefix = str_starts_with($identifier, $identifierPrefix);
                if ($hasPrefix) {
                    self::assertTrue(
                        $iconRegistry->isRegistered($identifier),
                        sprintf(
                            '%s names "%s", which is not registered, so the backend renders the '
                            . 'not-found placeholder for it.',
                            $where,
                            $identifier,
                        ),
                    );
                    self::assertFalse(
                        $iconRegistry->isDeprecated($identifier),
                        sprintf('%s names "%s", which is deprecated.', $where, $identifier),
                    );
                } elseif (!$iconRegistry->isRegistered($identifier) || $iconRegistry->isDeprecated($identifier)) {
                    // Not ours, and reading the configuration of a deprecated icon raises
                    // E_USER_DEPRECATED.
                    continue;
                }
                $configuration = $iconRegistry->getIconConfigurationByIdentifier($identifier);
                if (!$hasPrefix && !str_starts_with((string)($configuration['options']['source'] ?? ''), $sourcePrefix)) {
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
     * The checks for the icon of a type the extension owns, see
     * {@see self::assertEveryRecordTypeIconIsColourSchemeAware()}.
     */
    private function assertOwnedTypeIconIsColourSchemeAware(string $where, mixed $identifier, string $identifierPrefix): void
    {
        self::assertIsString($identifier, sprintf('%s is not an icon identifier.', $where));
        self::assertNotSame('', $identifier, sprintf('%s is empty, so the type has no icon.', $where));
        self::assertStringNotContainsString(
            '/',
            $identifier,
            sprintf(
                '%s names the file "%s" instead of a registered icon identifier, so the backend '
                . 'renders the not-found placeholder for it.',
                $where,
                $identifier,
            ),
        );
        self::assertStringStartsWith(
            $identifierPrefix,
            $identifier,
            sprintf(
                '%s names "%s". A type of this extension names an icon of its own, %s<group>-<name>, '
                . 'never a core or a foreign one.',
                $where,
                $identifier,
                $identifierPrefix,
            ),
        );
        $iconRegistry = $this->get(IconRegistry::class);
        self::assertTrue(
            $iconRegistry->isRegistered($identifier),
            sprintf(
                '%s names "%s", which is not registered, so the backend renders the not-found '
                . 'placeholder for it.',
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
            sprintf(
                'The record icon "%s" of %s is not registered with the colour scheme aware provider.',
                $identifier,
                $where,
            ),
        );
        $this->assertIconIsInlinedInBothMarkups($identifier);
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    /**
     * The house format of the Font Awesome Free icons the academic extensions ship, as
     * it reaches the page: one 640 unit grid for every icon, so a row of icons does not
     * jump in size; `fill="currentColor"` on the root element; no `style` attribute; and
     * `width="1em" height="1em"`.
     *
     * The last two are what a frontend page needs. `.icon svg { width: 100%; height: 100% }`
     * exists in the backend stylesheet only, so on a frontend page an inlined `<svg viewBox>`
     * without its own size fills its container. Both core versions keep the two attributes,
     * and the backend overrides them inside `.icon` anyway, so they cost a backend-only icon
     * nothing.
     */
    private function assertIconIsInTheHouseFormat(string $identifier): void
    {
        $markup = $this->getColourSchemeAwareIcon($identifier)->getMarkup();
        self::assertMatchesRegularExpression(
            '/^<svg\b[^>]*>/',
            $markup,
            sprintf('Icon "%s" is not inlined.', $identifier),
        );
        preg_match('/^<svg\b[^>]*>/', $markup, $matches);
        $rootElement = $matches[0];

        foreach (['viewBox="0 0 640 640"', 'width="1em"', 'height="1em"', 'fill="currentColor"'] as $attribute) {
            self::assertStringContainsString(
                ' ' . $attribute,
                $rootElement,
                sprintf('The root element of icon "%s" does not carry %s: %s', $identifier, $attribute, $rootElement),
            );
        }
        self::assertDoesNotMatchRegularExpression(
            '/\sstyle="/',
            $markup,
            sprintf('Icon "%s" carries a style attribute.', $identifier),
        );
    }

    /**
     * Every identifier the extension registers in its `Configuration/Icons.php` follows
     * `tx-<extension key without underscores>-<group>-<name>`, is registered with
     * {@see CurrentColorSvgIconProvider}, and names an SVG file below
     * `Resources/Public/Icons/<directory>/` of some extension, in lowercase kebab case.
     *
     * The registry is flat and a duplicate registration silently wins, so the extension key
     * is the only token that keeps two extensions apart; it is written without underscores
     * because a dashed key is ambiguous in this family (`academic_persons` + `edit-print` and
     * `academic_persons_edit` + `print` would both read `academic-persons-edit-print`).
     *
     * The identifiers are read out of the extension's `Configuration/Icons.php` rather than
     * the registry, because the registry cannot tell which extension registered what.
     *
     * @param list<string> $groups the identifier groups the extension may use
     */
    private function assertIconIdentifiersFollowTheNamingScheme(string $extensionKey, array $groups): void
    {
        $icons = $this->getIconsDeclaredByExtension($extensionKey);
        self::assertNotSame([], $icons, sprintf('EXT:%s registers no icon - the check asserted nothing.', $extensionKey));

        $identifierPattern = sprintf(
            '/^tx-%s-(%s)-[a-z0-9]+(-[a-z0-9]+)*$/',
            preg_quote(str_replace('_', '', $extensionKey), '/'),
            implode('|', array_map(static fn(string $group): string => preg_quote($group, '/'), $groups)),
        );
        foreach ($icons as $identifier => $configuration) {
            self::assertMatchesRegularExpression(
                $identifierPattern,
                $identifier,
                sprintf('The icon identifier "%s" of EXT:%s does not follow the naming scheme.', $identifier, $extensionKey),
            );
            self::assertSame(
                CurrentColorSvgIconProvider::class,
                $configuration['provider'] ?? null,
                sprintf('Icon "%s" is not registered with the colour scheme aware provider.', $identifier),
            );
            self::assertMatchesRegularExpression(
                '#^EXT:[a-z0-9_]+/Resources/Public/Icons/[a-z0-9]+(-[a-z0-9]+)*/[a-z0-9]+(-[a-z0-9]+)*\.svg$#',
                (string)($configuration['source'] ?? ''),
                sprintf('The source of icon "%s" is not an SVG file below Icons/<group>/.', $identifier),
            );
        }
    }

    /**
     * The stricter layout rule for an extension whose icons do not share files: the file of
     * `tx-<key>-<group>-<name>` is `EXT:<key>/Resources/Public/Icons/<group>/<name>.svg`.
     * An extension whose record icon reuses a shared glyph does not follow it, and does not
     * call this.
     */
    private function assertIconFilesAreNamedAfterTheirIdentifiers(string $extensionKey): void
    {
        $icons = $this->getIconsDeclaredByExtension($extensionKey);
        self::assertNotSame([], $icons, sprintf('EXT:%s registers no icon - the check asserted nothing.', $extensionKey));

        $prefix = 'tx-' . str_replace('_', '', $extensionKey) . '-';
        foreach ($icons as $identifier => $configuration) {
            self::assertStringStartsWith($prefix, $identifier);
            [$group, $name] = explode('-', substr($identifier, strlen($prefix)), 2) + [1 => ''];
            self::assertSame(
                sprintf('EXT:%s/Resources/Public/Icons/%s/%s.svg', $extensionKey, $group, $name),
                $configuration['source'] ?? null,
                sprintf('The file of icon "%s" is not named after the identifier.', $identifier),
            );
        }
    }

    /**
     * An icon file nothing registers is covered by no other assertion: every other check
     * walks registrations, and an orphan is not one. The day it gets registered it has
     * never been looked at. So every SVG file below `Resources/Public/Icons/` of the
     * extension has to be the source of at least one registered identifier - registered by
     * any loaded extension, since a shared file may be used by another one.
     *
     * The exempt files have to exist: `Extension.svg` is the icon of the extension manager
     * and the TER, which read the file rather than the registry.
     *
     * @param list<string> $exemptFiles paths relative to `Resources/Public/Icons/`
     */
    private function assertEveryIconFileIsRegistered(string $extensionKey, array $exemptFiles = ['Extension.svg']): void
    {
        $iconDirectory = $this->getIconDirectoryOfExtension($extensionKey);
        foreach ($exemptFiles as $exemptFile) {
            self::assertFileExists($iconDirectory . $exemptFile);
        }

        $sourcePrefix = 'EXT:' . $extensionKey . '/Resources/Public/Icons/';
        $iconRegistry = $this->get(IconRegistry::class);
        $registeredFiles = [];
        foreach ($iconRegistry->getAllRegisteredIconIdentifiers() as $identifier) {
            // Asking for the configuration of a deprecated icon raises E_USER_DEPRECATED.
            if ($iconRegistry->isDeprecated($identifier)) {
                continue;
            }
            $source = (string)($iconRegistry->getIconConfigurationByIdentifier($identifier)['options']['source'] ?? '');
            if (str_starts_with($source, $sourcePrefix)) {
                $registeredFiles[substr($source, strlen($sourcePrefix))] = true;
            }
        }

        $files = $this->getSvgFilesBelow($iconDirectory);
        self::assertNotSame([], $files, sprintf('EXT:%s ships no icon file - the check asserted nothing.', $extensionKey));
        foreach ($files as $file) {
            if (in_array($file, $exemptFiles, true)) {
                continue;
            }
            self::assertArrayHasKey(
                $file,
                $registeredFiles,
                sprintf('%s%s is the source of no registered icon. Register it or delete it.', $sourcePrefix, $file),
            );
        }
    }

    /**
     * Font Awesome Free icons are CC BY 4.0, and the attribution comment in the file never
     * reaches the page - the sanitiser removes comments. The attribution is the notice file
     * next to the icons, which lists every file it covers, so a file added without being
     * listed there ships without its licence. Every SVG file apart from the exempt ones
     * carries the Font Awesome Free comment and is listed in the notice.
     *
     * @param list<string> $exemptFiles paths relative to `Resources/Public/Icons/`
     */
    private function assertEveryIconFileIsAttributedInTheNotice(
        string $extensionKey,
        array $exemptFiles = ['Extension.svg'],
        string $noticeFile = 'LICENSE-font-awesome.txt',
    ): void {
        $iconDirectory = $this->getIconDirectoryOfExtension($extensionKey);
        self::assertFileExists($iconDirectory . $noticeFile);
        $notice = (string)file_get_contents($iconDirectory . $noticeFile);

        $files = array_values(array_diff($this->getSvgFilesBelow($iconDirectory), $exemptFiles));
        self::assertNotSame([], $files, sprintf('EXT:%s ships no icon file - the check asserted nothing.', $extensionKey));
        foreach ($files as $file) {
            self::assertMatchesRegularExpression(
                '/^\s+' . preg_quote($file, '/') . '\s+[a-z0-9-]+$/m',
                $notice,
                sprintf('%s is not listed in %s.', $file, $noticeFile),
            );
            self::assertStringContainsString(
                '<!--! Font Awesome Free ',
                (string)file_get_contents($iconDirectory . $file),
                sprintf('%s does not carry the Font Awesome Free attribution comment.', $file),
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getIconsDeclaredByExtension(string $extensionKey): array
    {
        $packagePath = $this->get(PackageManager::class)->getPackage($extensionKey)->getPackagePath();
        $iconsFile = $packagePath . 'Configuration/Icons.php';
        if (!is_file($iconsFile)) {
            return [];
        }
        $icons = require $iconsFile;
        self::assertIsArray($icons, sprintf('EXT:%s/Configuration/Icons.php does not return an array.', $extensionKey));

        return $icons;
    }

    private function getIconDirectoryOfExtension(string $extensionKey): string
    {
        $iconDirectory = $this->get(PackageManager::class)->getPackage($extensionKey)->getPackagePath()
            . 'Resources/Public/Icons/';
        self::assertDirectoryExists($iconDirectory);

        return $iconDirectory;
    }

    /**
     * @return list<string> paths relative to `$directory`, sorted
     */
    private function getSvgFilesBelow(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && strtolower($file->getExtension()) === 'svg') {
                $files[] = substr($file->getPathname(), strlen($directory));
            }
        }
        sort($files);

        return $files;
    }

    private function getColourSchemeAwareIcon(string $identifier): Icon
    {
        return $this->get(IconFactory::class)->getIcon($identifier, IconSize::SMALL);
    }
}
