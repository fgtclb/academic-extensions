<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment\Exception;

use FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactory;
use FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactoryInterface;
use FGTCLB\AcademicBase\Environment\EnvironmentBuilderInterface;

/**
 * Indicates that no suitable {@see EnvironmentBuilderInterface} implementation can be found
 * in {@see EnvironmentBuilderFactoryInterface::create()} ({@see EnvironmentBuilderFactory::create()}).
 *
 * @deprecated since academic_base 2.4.0, will be removed in academic_base 3.0.0.
 *             Use the fgtclb/environment-state-manager extension instead
 *             (namespace FGTCLB\EnvironmentStateManager).
 */
final class NoTypo3VersionCompatibleEnvironmentBuilderFound extends \RuntimeException {}
