<?php

declare(strict_types=1);

namespace TESTS\TestPluginViewEvent\EventListener;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
use FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent;
use FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent;
use FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent;
use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;
use FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent;
use FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent;
use FGTCLB\AcademicPrograms\Event\ModifyProgramListEvent;
use FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent;
use FGTCLB\AcademicProjects\Event\ModifyProjectListEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Records the plugin context of every event of the academic plugins that carries one, in the
 * order the events are dispatched, so a test can tell whether the events of one rendering
 * received the same context. A test resets it before it renders.
 */
final class RecordPluginContextListener
{
    /**
     * @var list<array{event: string, context: PluginControllerActionContextInterface}>
     */
    public static array $contexts = [];

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-view')]
    public function onPluginView(ModifyPluginViewEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-partner-demand')]
    public function onPartnerDemand(ModifyPartnerDemandEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-partner-list')]
    public function onPartnerList(ModifyPartnerListEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-program-demand')]
    public function onProgramDemand(ModifyProgramDemandEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-program-list')]
    public function onProgramList(ModifyProgramListEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-project-demand')]
    public function onProjectDemand(ModifyProjectDemandEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-project-list')]
    public function onProjectList(ModifyProjectListEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-profile-query')]
    public function onProfileQuery(ModifyProfileQueryEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-contract-query')]
    public function onContractQuery(ModifyContractQueryEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-view-event/context-of-profile-title')]
    public function onProfileTitle(ModifyProfileTitlePlaceholderReplacementEvent $event): void
    {
        $this->record($event, $event->getPluginControllerActionContext());
    }

    /**
     * A query event dispatched without a plugin, by a command for example, carries none.
     */
    private function record(object $event, ?PluginControllerActionContextInterface $context): void
    {
        if ($context === null) {
            return;
        }
        self::$contexts[] = [
            'event' => (new \ReflectionClass($event))->getShortName(),
            'context' => $context,
        ];
    }
}
