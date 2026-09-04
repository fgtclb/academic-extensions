<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings\Exception;

use TYPO3\CMS\Core\Exception;

/**
 * Thrown when a validator is handed a subject it is not built for.
 */
final class UnsuitableValidatorException extends Exception {}
