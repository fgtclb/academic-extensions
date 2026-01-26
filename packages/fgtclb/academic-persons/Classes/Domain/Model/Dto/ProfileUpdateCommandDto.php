<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Model\Dto;

/**
 * DTO for passing configuration to ProfileUpdateCommandService.
 */
final readonly class ProfileUpdateCommandDto
{
    /**
     * @param int[] $includePids PIDs to include in processing
     * @param int[] $excludePids PIDs to exclude from processing
     */
    public function __construct(
        public array $includePids = [],
        public array $excludePids = [],
    ) {}
}
