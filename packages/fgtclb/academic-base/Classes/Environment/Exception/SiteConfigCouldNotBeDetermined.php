<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment\Exception;

use FGTCLB\AcademicBase\Environment\EnvironmentBuilderInterface;

/**
 * This exception indicates that no suiting SiteConfiguration could be automatically determined.
 *
 * Thrown in {@see EnvironmentBuilderInterface::build()} implementations.
 *
 * @deprecated since academic_base 2.4.0, will be removed in academic_base 3.0.0.
 *             Use the fgtclb/environment-state-manager extension instead
 *             (namespace FGTCLB\EnvironmentStateManager).
 */
final class SiteConfigCouldNotBeDetermined extends \RuntimeException {}
