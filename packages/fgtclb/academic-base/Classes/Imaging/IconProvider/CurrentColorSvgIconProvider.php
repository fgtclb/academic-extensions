<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Imaging\IconProvider;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Inlines an SVG file as the icon markup - in the default markup as well as in the
 * `inline` alternative - so that an icon drawn in `currentColor` takes the colour of
 * the text around it. Core's `SvgIconProvider` renders the default markup as `<img>`,
 * which is opaque to CSS: such an icon keeps the colours of its file, whatever the
 * backend colour scheme or the frontend theme says.
 *
 * Opt in per icon from `Configuration/Icons.php`:
 *
 *     'my-icon' => [
 *         'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
 *         'source' => 'EXT:my_extension/Resources/Public/Icons/my-icon.svg',
 *     ],
 *
 * The file has to be drawn for inlining: a `viewBox`, `fill="currentColor"` or
 * `stroke="currentColor"` on the shapes, no hardcoded colours, no `id` attributes - the
 * markup may appear several times in one document - and no `<script>` or event handler
 * attributes. The sources are files an extension ships and registers itself, never
 * uploads: TYPO3 v14 sanitises the content, TYPO3 v13 strips `<script>` elements only.
 *
 * `AbstractSvgIconProvider` is `@internal` on both cores and TYPO3 v14 already rewrote its
 * internals once; re-read it on every core bump. Core's own `SvgIconProvider` extends it
 * the same way.
 *
 * No constructor on purpose. On TYPO3 v14 the parent class receives its collaborators
 * through `inject*()` setters, which the container wires for an autowired service only,
 * and it tags every `IconProviderInterface` as `icon.provider` and publishes it, so
 * `IconFactory` fetches the provider from the container. The class is therefore a
 * regular autowired service of `EXT:academic_base` and must not be excluded from the
 * container. TYPO3 v13 instantiates it with `new` and needs nothing.
 */
final class CurrentColorSvgIconProvider extends AbstractSvgIconProvider
{
    /**
     * @param array<string, mixed> $options
     */
    protected function generateMarkup(Icon $icon, array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException(
                '[' . $icon->getIdentifier() . '] The option "source" is required and must not be empty',
                1788480163,
            );
        }
        return $this->generateInlineMarkup($options);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function generateInlineMarkup(array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException(
                'The option "source" is required and must not be empty',
                1788480164,
            );
        }
        $source = (string)$options['source'];
        // TYPO3 v13 reads the file straight from disk and needs an absolute path. TYPO3 v14
        // resolves an `EXT:` path itself through `SystemResourceFactory`, and is handed the
        // path unchanged so that resolution is not bypassed.
        // @todo Remove the switch once TYPO3 v13 support is dropped.
        if ((new Typo3Version())->getMajorVersion() < 14
            && (PathUtility::isExtensionPath($source) || !PathUtility::isAbsolutePath($source))
        ) {
            $source = GeneralUtility::getFileAbsFileName($source);
        }
        return $this->getInlineSvg($source);
    }
}
