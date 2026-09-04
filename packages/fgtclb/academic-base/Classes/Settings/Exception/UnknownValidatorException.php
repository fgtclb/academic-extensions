<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings\Exception;

use TYPO3\CMS\Core\Exception;

/**
 * Thrown when a validation names a validator class that is not an Extbase validator.
 */
final class UnknownValidatorException extends Exception {}
