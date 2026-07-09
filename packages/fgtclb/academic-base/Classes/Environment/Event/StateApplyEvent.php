<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment\Event;

use FGTCLB\AcademicBase\Environment\StateInterface;
use FGTCLB\AcademicBase\Environment\StateManagerRootStateInterfaceHelperMethodsTrait;

/**
 * This event is dispatched in {@see StateManagerRootStateInterfaceHelperMethodsTrait::dispatchStateApplyEvent()},
 * used by TYPO3 version related implementation to allow applying for example additional custom state.
 *
 * @deprecated since academic_base 2.4.0, will be removed in academic_base 3.0.0.
 *             Use the fgtclb/environment-state-manager extension instead
 *             (namespace FGTCLB\EnvironmentStateManager).
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
