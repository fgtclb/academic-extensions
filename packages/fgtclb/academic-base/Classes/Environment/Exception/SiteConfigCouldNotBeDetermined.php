<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment\Exception;

use FGTCLB\AcademicBase\Environment\EnvironmentBuilderInterface;

/**
 * This exception indicates that no suiting SiteConfiguration could be automatically determined.
 *
 * Thrown in {@see EnvironmentBuilderInterface::build()} implementations.
 */
final class SiteConfigCouldNotBeDetermined extends \RuntimeException {}
