<?php

declare(strict_types=1);

namespace TESTS\TestProjectListEvents\EventListener;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Changes the project demand, driven by plugin settings so that one fixture extension
 * serves every scenario: a test includes the TypoScript file of the behaviour it wants and
 * the listener stays inert for every other test of the same class.
 */
final class ProjectDemandListener
{
    #[AsEventListener(identifier: 'test-project-list-events/demand')]
    public function __invoke(ModifyProjectDemandEvent $event): void
    {
        $context = $event->getPluginControllerActionContext();
        $settings = $context->getSettings();

        // A listener that acts for one of the two project list plugins only. The plugin
        // name is the one the plugin was registered with ("ProjectList",
        // "ProjectListSingle"), not the content element type.
        $onlyForPlugin = (string)($settings['testProjectDemandOnlyForPlugin'] ?? '');
        if ($onlyForPlugin !== '' && $onlyForPlugin !== $context->getPluginName()) {
            return;
        }

        $activeState = (string)($settings['testProjectDemandActiveState'] ?? '');

        // Hands back a demand the listener built itself rather than mutating the one the
        // controller passed. That is what makes the *replacement* observable: a listener
        // that only mutates would be adopted even by a controller that threw the event's
        // demand away. `showSelected` travels with `pages` because the repository reads
        // the one as the selected uids and the other as storage pages.
        //
        // This branch stands on its own: it replaces whether or not an active state is
        // asked for.
        if ((string)($settings['testProjectDemandReplaceDemand'] ?? '') === '1') {
            $replacement = new ProjectDemand();
            $replacement->setPages($event->getDemand()->getPages());
            $replacement->setShowSelected($event->getDemand()->getShowSelected());
            if ($activeState !== '') {
                $replacement->setActiveState($activeState);
            }
            $event->setDemand($replacement);
            return;
        }

        if ($activeState !== '') {
            $event->getDemand()->setActiveState($activeState);
        }
    }
}
