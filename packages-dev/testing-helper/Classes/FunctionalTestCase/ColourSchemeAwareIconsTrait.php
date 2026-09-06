<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

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

    private function getColourSchemeAwareIcon(string $identifier): Icon
    {
        return $this->get(IconFactory::class)->getIcon($identifier, IconSize::SMALL);
    }
}
