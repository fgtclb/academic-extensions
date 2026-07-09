<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment;

use FGTCLB\AcademicBase\Environment\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;

use FGTCLB\EnvironmentStateManager\StateBuildContext;

/**
 * Interface for environment builder factory implementation.
 *
 * @deprecated since academic_base 2.4.0, will be removed in academic_base 3.0.0.
 *             Use the fgtclb/environment-state-manager extension instead
 *             (namespace FGTCLB\EnvironmentStateManager).
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
interface EnvironmentBuilderFactoryInterface
{
    /**
     * Create a environment builder instance.
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     */
    public function create(StateBuildContext $stateBuildContext): EnvironmentBuilderInterface;
}
