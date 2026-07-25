<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

define('ORIGINAL_ROOT', dirname(__FILE__, 2) . '/');

// Mirrors packages/fgtclb/academic-persons/EXT_CONSTANTS.php for static analysis.
// On TYPO3 v14 the Extbase #[Cascade] attribute takes the plain string form.
defined('ACADEMIC_PERSONS_CASCADE_REMOVE') || define('ACADEMIC_PERSONS_CASCADE_REMOVE', 'remove');
defined('ACADEMIC_JOBS_CASCADE_REMOVE') || define('ACADEMIC_JOBS_CASCADE_REMOVE', 'remove');
