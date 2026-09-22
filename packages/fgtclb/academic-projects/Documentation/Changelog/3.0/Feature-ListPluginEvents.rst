..  _feature-list-plugin-events:

==========================================
Feature: Change the project lists by event
==========================================

Description
===========

:php:`\FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent` and
:php:`\FGTCLB\AcademicProjects\Event\ModifyProjectListEvent` are PSR-14 events
dispatched by both project list plugins.

The demand event fires after the demand has been built from the content element
settings and the request and before the projects are queried; the demand a
listener hands back is the one that is queried and assigned to the view. The
list event fires after the query and before the view variables are assigned; a
listener replaces the projects, replaces the applicable categories, or assigns
further view variables.

Both carry
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`,
so a listener knows the request, the site, the content element, its settings and
which of the two list plugins is rendering.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ShowRunningProjectsOnly.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class ShowRunningProjectsOnly
    {
        #[AsEventListener(identifier: 'my-extension/show-running-projects-only')]
        public function __invoke(ModifyProjectDemandEvent $event): void
        {
            if ($event->getPluginControllerActionContext()->getPluginName() === 'ProjectList') {
                $event->getDemand()->setActiveState('active');
            }
        }
    }

Impact
======

Nothing changes in an installation that has no listener. A project that
subclasses :php:`ProjectController` to adjust the demand, the result or the view
variables can drop the subclass and the plugin re-registration that goes with
it, and listen instead.

One detail is worth knowing: the applicable categories are computed once, before
the list event, and not recomputed afterwards - a listener that replaces the
projects and wants the filter to match them sets the categories as well.

..  warning::

    A demand listener widens as easily as it narrows. The demand *is* the
    query, so :php:`setShowHiddenRecords(true)` shows hidden project pages to
    every visitor, :php:`setPages([])` drops the storage restriction the editor
    chose, and :php:`setSorting()` overrides the editor's ordering. What a
    listener cannot undo is the page type, the enable fields other than
    :php:`disabled` and the :php:`uid` tiebreaker of the ordering. A result
    handed to :php:`setProjects()` is rendered in the order it carries - the
    ordering of the repository is not reapplied - and a demand built from
    scratch rather than mutated drops the editor's settings, the category
    filter the visitor submitted and, with :php:`showSelected`, the meaning of
    :php:`pages`.

..  index:: Frontend, PHP-API, ext:academic_projects
