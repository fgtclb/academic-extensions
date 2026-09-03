<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\EventListener;

use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Profile\AbstractProfileFactory;
use FGTCLB\AcademicPersonsEdit\Controller\ProfileController;
use FGTCLB\AcademicPersonsEdit\EventListener\SyncChangesToTranslations;
use FGTCLB\AcademicPersonsEdit\Tests\Functional\AbstractAcademicPersonsEditTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;

/**
 * Pins the dispatch surface of {@see AfterProfileUpdateEvent} for ACE-485.
 *
 * The profile controller is the sole frontend editing dispatch surface. The source pin
 * complements the functional profile editing translation test and the listener tests.
 */
final class SyncChangesToTranslationsDispatchSurfaceTest extends AbstractAcademicPersonsEditTestCase
{
    #[Test]
    public function profileControllerDispatchesUpdatesThroughItsPersistenceHelper(): void
    {
        $controllerSource = (string)file_get_contents(
            (string)(new \ReflectionClass(ProfileController::class))->getFileName(),
        );
        $this->assertStringContainsString(
            '$this->persistAndDispatchProfileUpdate(',
            $controllerSource,
        );
        $this->assertStringContainsString(
            '->dispatch(new AfterProfileUpdateEvent(',
            $controllerSource,
        );
    }

    /**
     * Positive control for the source scans above, and a pin of its own: the profile
     * auto-creation dispatch of `EXT:academic_persons` is unchanged by ACE-485.
     */
    #[Test]
    public function profileFactoryStillDispatchesTheEventAfterProfileCreation(): void
    {
        $factorySource = (string)file_get_contents(
            (string)(new \ReflectionClass(AbstractProfileFactory::class))->getFileName(),
        );
        $this->assertStringContainsString('AfterProfileUpdateEvent', $factorySource);
        $this->assertStringContainsString('->dispatch($afterProfileUpdatedEvent)', $factorySource);
    }

    /**
     * The listening side: the sync listener IS registered for the event, so every
     * dispatch with a persisted default language profile runs the synchronisation.
     */
    #[Test]
    public function syncListenerIsRegisteredForAfterProfileUpdateEvent(): void
    {
        $listenerProvider = $this->get(ListenerProvider::class);
        $services = array_column(
            $listenerProvider->getAllListenerDefinitions()[AfterProfileUpdateEvent::class] ?? [],
            'service',
        );
        $this->assertContains(SyncChangesToTranslations::class, $services);
    }
}
