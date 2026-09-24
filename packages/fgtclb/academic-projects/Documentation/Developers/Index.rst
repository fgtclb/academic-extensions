..  _developers:

==============
For developers
==============

Both project list plugins - :guilabel:`Project list` and
:guilabel:`Project list (single selection)` - dispatch two PSR-14 events. They
are the supported way to change what a plugin queries and what it renders, and
they replace the only alternative there used to be: subclassing
:php:`ProjectController` and re-registering the plugin.

..  _developers-project-events:

The two events
==============

..  list-table::
    :header-rows: 1

    *   -   Event
        -   Dispatched
        -   A listener may
    *   -   :php:`\FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent`
        -   in :php:`listAction()`, after the demand is built from the content
            element settings and the request and before the projects are
            queried
        -   replace the demand
    *   -   :php:`\FGTCLB\AcademicProjects\Event\ModifyProjectListEvent`
        -   in the same action, after the query and before the view variables
            are assigned
        -   replace the projects, replace the applicable categories, assign
            further view variables

One action serves both plugins, so both events fire for both. Which one is
rendering is on the context,
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`,
which :php:`getPluginControllerActionContext()` returns: the request, the site
and its language, the content object of the element, the settings of the
content element and the plugin name.

Neither event fires for a submission of the filter and sorting form: the plugin
answers the POST with a redirect to a URL carrying the selection before the
demand event, and both events fire on the request that follows, with the
selection in the query string (or the route), not in the parsed body. See
:ref:`feature-1790226101`.

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
            if ($event->getPluginControllerActionContext()->getPluginName() !== 'ProjectList') {
                return;
            }
            $event->getDemand()->setActiveState('active');
        }
    }

The plugin names are the names the plugins were **registered** with -
:php:`ProjectList` and :php:`ProjectListSingle` - not the content element
types.

:php:`ModifyProjectListEvent` carries the view as well, so a listener assigns
values a project template renders:

..  code-block:: php

    $event->getView()->assign('projectCount', count($event->getProjects()->toArray()));

..  _developers-project-events-rules:

Rules worth knowing
===================

..  warning::

    **A demand listener widens as easily as it narrows.** The demand *is* the
    query here, not a constraint added to one, and almost nothing guards it:
    :php:`setShowHiddenRecords(true)` shows hidden project pages to every
    visitor, :php:`setPages([])` drops the storage restriction the editor chose
    in the content element, and :php:`setSorting()` overrides the editor's
    ordering for every element that renders - though only for a value that is
    one of the :php:`SortingOptions` constants, since anything else is ignored
    without a word. Two listeners that disagree are resolved by the order they
    run in - the last one wins.

    What a listener cannot undo: the page type is pinned unconditionally,
    :php:`showHiddenRecords` reaches the :php:`disabled` flag alone (start- and
    endtime, :php:`fe_group` and :php:`deleted` stay in effect), and a
    :php:`uid` tiebreaker is always appended to the ordering.

**A replaced demand starts from the defaults.** :php:`setDemand()` is there for
a listener that builds its own demand, and such a demand carries none of what
the plugin put in the one it was handed: the :php:`showHiddenRecords` choice of
the editor, and the three the visitor can set themselves through the filter
form - the :php:`sorting`, the :php:`activeState` and the
:php:`filterCollection` - so a replacement resets the ordering, the state
filter and the category filter under them. :php:`showSelected` has to travel
with :php:`pages` or it changes what they mean: the repository reads
:php:`pages` as the selected project uids when :php:`showSelected` is true and
as storage page uids when it is false, so a single-selection element whose
demand is replaced without it turns into a storage-folder restriction. Mutate
the demand where that is enough, and carry :php:`getPages()`,
:php:`getShowSelected()`, :php:`getShowHiddenRecords()`,
:php:`getActiveState()`, :php:`getFilterCollection()` and :php:`getSorting()`
over where it is not.

**A replaced result is rendered as it is.** :php:`setProjects()` takes whatever
query result a listener hands back, and the ordering of the repository is not
reapplied to it. A listener that builds its own result gives it its own
ordering, or the list is in whatever order the database happens to return -
which is not the same list twice on PostgreSQL.

**The categories are not recomputed.** They are computed from the queried
projects, once, before the list event. A listener that replaces the projects
and wants the filter of the plugin to match them sets the categories too:

..  code-block:: php

    $event->setProjects($narrowedResult);
    $event->setCategories(
        $this->categoryRepository->findAllApplicable('projects', ...$narrowedResult->toArray()),
    );

:php:`findAllApplicable()` is what the controller itself calls: it keeps every
category of the group and marks the ones no record carries as disabled options.
:php:`findByGroupAndUidList()` returns a bare list instead, so a listener that
reaches for that one drops the disabled options the filter otherwise shows.

Nothing changes in an installation that has no listener: both plugins query and
render exactly what they did before.

..  index:: Frontend, PHP-API, ext:academic_projects
