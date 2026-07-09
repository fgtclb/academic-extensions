<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment;

use FGTCLB\EnvironmentStateManager\StateBuildContext;

/**
 * Interface for concrete environment builder implementations.
 *
 * @deprecated since academic_base 2.4.0, will be removed in academic_base 3.0.0.
 *             Use the fgtclb/environment-state-manager extension instead
 *             (namespace FGTCLB\EnvironmentStateManager).
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
interface EnvironmentBuilderInterface
{
    /**
     * Build environment configured by passed $buildContext.
     */
    public function build(StateBuildContext $stateBuildContext): StateInterface;
}
