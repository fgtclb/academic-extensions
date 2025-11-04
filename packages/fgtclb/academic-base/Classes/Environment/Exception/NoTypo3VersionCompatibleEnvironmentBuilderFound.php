<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment\Exception;

use FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactory;
use FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactoryInterface;
use FGTCLB\AcademicBase\Environment\EnvironmentBuilderInterface;

/**
 * Indicates that no suitable {@see EnvironmentBuilderInterface} implementation can be found
 * in {@see EnvironmentBuilderFactoryInterface::create()} ({@see EnvironmentBuilderFactory::create()}).
 */
final class NoTypo3VersionCompatibleEnvironmentBuilderFound extends \RuntimeException {}
