<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Core12\Environment;

use FGTCLB\AcademicBase\Environment\StateInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Context\TypoScriptAspect;

/**
 * Extended state interface for TYPO3 v12 specific methods.
 *
 * Note that `#[Exclude]` is used intentionally to avoid automatic early compiling into the
 * dependency injection container leading to missing class and other issues for not related
 * TYPO3 version. TYPO3 version aware configuration is handled and re_enabled within the
 * `EXT:academic_base/Configuration/Services.php` file.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
#[Exclude]
interface ExtendedStateInterface extends StateInterface
{
    public function withTypoScriptAspect(?TypoScriptAspect $typoScriptAspect = null): self;
    public function typoScriptAspect(): ?TypoScriptAspect;
}
