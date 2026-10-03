<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Imaging;

use TYPO3\CMS\Core\Imaging\Icon;

/**
 * An icon of the frontend icon registry. It overrides nothing, so it renders exactly
 * the wrapper markup of `core:icon` on every core version, and every icon provider
 * accepts it, because they type hint the core `Icon`.
 *
 * A class of its own for two reasons. {@see FrontendIconFactory} creates it with
 * `new`, so an XCLASS of the core `Icon`, which core creates through
 * `GeneralUtility::makeInstance()`, never reaches the frontend. And a leaner frontend
 * markup, should it ever come, overrides `wrappedIcon()` here as a change of its own,
 * without touching a provider or the factory.
 *
 * @internal not part of public API.
 */
final class FrontendIcon extends Icon {}
