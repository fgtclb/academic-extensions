<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Core13\Environment;

use FGTCLB\AcademicBase\Environment\StateInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Extended state interface for TYPO3 v13 specific methods.
 *
 * Note that `#[Exclude]` is used intentionally to avoid automatic early compiling into the
 * dependency injection container leading to missing class and other issues for not related
 * TYPO3 version. TYPO3 version aware configuration is handled and re_enabled within the
 * `EXT:academic_base/Configuration/Services.php` file.
 *
 * @deprecated since academic_base 2.4.0, will be removed in academic_base 3.0.0.
 *             Use the fgtclb/environment-state-manager extension instead
 *             (namespace FGTCLB\EnvironmentStateManager).
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
#[Exclude]
interface ExtendedStateInterface extends StateInterface {}
