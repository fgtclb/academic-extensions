<?php

declare(strict_types=1);

namespace TESTS\TestPluginViewEvent\EventListener;

use FGTCLB\AcademicPersons\Event\ModifyListProfilesEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * A listener a project wrote for the persons list event before 3.0 removed it, and never
 * migrated. It records each call, so a test can show it is no longer called, and that the
 * container is built and the list renders all the same.
 */
final class LeftoverListProfilesListener
{
    public static int $calls = 0;

    #[AsEventListener(identifier: 'test-plugin-view-event/leftover-list-profiles')]
    // The event class is gone, and this is the report a project gets for such a listener.
    // @phpstan-ignore class.notFound
    public function __invoke(ModifyListProfilesEvent $event): void
    {
        self::$calls++;
    }
}
