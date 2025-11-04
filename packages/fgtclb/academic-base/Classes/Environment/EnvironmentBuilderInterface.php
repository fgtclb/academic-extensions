<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment;

/**
 * Interface for concrete environment builder implementations.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
interface EnvironmentBuilderInterface
{
    /**
     * Build environment configured by passed $buildContext.
     */
    public function build(StateBuildContext $stateBuildContext): StateInterface;
}
