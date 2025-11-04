<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment;

use FGTCLB\AcademicBase\Environment\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;

/**
 * Interface for environment builder factory implementation.
 *
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
