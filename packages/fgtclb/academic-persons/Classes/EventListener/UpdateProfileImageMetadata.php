<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\EventListener;

use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;

/**
 * Updates profile image metadata whenever a profile is changed.
 *
 * @internal to be used only in EXT:academic_persons and not part of public API.
 */
final class UpdateProfileImageMetadata
{
    public function __construct(
        private readonly ProfileImageMetadataService $profileImageMetadataService,
    ) {}

    public function __invoke(AfterProfileUpdateEvent $event): void
    {
        $this->profileImageMetadataService->update($event->getProfile());
    }
}
