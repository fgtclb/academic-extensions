<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment\Event;

use FGTCLB\AcademicBase\Environment\StateInterface;
use FGTCLB\AcademicBase\Environment\StateManagerRootStateInterfaceHelperMethodsTrait;

/**
 * This event is dispatched in {@see StateManagerRootStateInterfaceHelperMethodsTrait::dispatchStateApplyEvent()},
 * used by TYPO3 version related implementation to allow applying for example additional custom state.
 *
 * @internal for internal usage only and not part of public API.
 */
final class StateApplyEvent
{
    public function __construct(
        private readonly StateInterface $state,
    ) {}

    public function getState(): StateInterface
    {
        return $this->state;
    }
}
