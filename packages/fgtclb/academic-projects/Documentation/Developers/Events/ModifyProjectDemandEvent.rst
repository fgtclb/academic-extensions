..  _developers-event-modify-project-demand-event:

========================
ModifyProjectDemandEvent
========================

:php:`\FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent` hands a
listener the demand a project list is about to be queried with. The demand a
listener hands back is the one that is queried and assigned to the view as
:html:`{demand}`.

When it is dispatched
=====================

:php:`ProjectController::listAction()` dispatches the event once per rendering
of the :guilabel:`Projects` and the :guilabel:`Selected Projects` content
elements, after the demand is built from the settings of the content element
and the selection of the visitor, and before the projects are queried.
:ref:`developers-event-modify-project-list-event` follows once the projects
are queried.

The event does not fire for a submission of the filter and sorting form: the
plugin answers the POST with a redirect to a URL carrying the selection before
the event, and the event fires on the request that follows, with the selection
in the query string (or the route), not in the parsed body. See
:ref:`feature-1790226101`.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Method
        -   What it does
    *   -   :php:`getDemand()`
        -   The demand,
            :php:`\FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand`.
            It is an object, so a change to it takes effect without
            :php:`setDemand()`.
    *   -   :php:`setDemand()`
        -   Replaces the demand that is queried.
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the rendering,
            :php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`:
            the request, the site and its language, the content object of the
            element, the settings of the content element and the plugin name.

What the query reads from the demand:

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Getter and setter
        -   Meaning
    *   -   :php:`getPages()`, :php:`setPages()`
        -   The uids of the field below the options: the pages the projects sit
            directly in, or the picked project pages. Empty means every
            project page of the installation.
    *   -   :php:`getShowSelected()`, :php:`setShowSelected()`
        -   How the pages are read: :php:`true` for :guilabel:`Selected
            Projects`, where they are the project pages themselves, matched
            across languages, :php:`false` for :guilabel:`Projects`, where they
            are the parent pages.
    *   -   :php:`getActiveState()`, :php:`setActiveState()`
        -   `all`, `active` or `completed`. The setter throws an
            :php:`\InvalidArgumentException` for any other value.
    *   -   :php:`getFilterCollection()`, :php:`setFilterCollection()`
        -   The selected categories, those of the visitor or the
            :guilabel:`Categories` of the element, as a
            :php:`\FGTCLB\CategoryTypes\Collection\FilterCollection`, or
            :php:`null` for none. A project has to carry every one of them.
    *   -   :php:`getSorting()`, :php:`setSorting()`, and the field and the
            direction on their own
        -   The ordering, one of the values of
            :php:`\FGTCLB\AcademicProjects\Enumeration\SortingOptions`, for
            example `tx_academicprojects_start_date desc`.
    *   -   :php:`getShowHiddenRecords()`, :php:`setShowHiddenRecords()`
        -   Whether hidden project pages are listed, the field
            :guilabel:`Show hidden records` of the element.

The plugin names are the names the plugins were **registered** with,
:php:`ProjectList` and :php:`ProjectListSingle`, not the content element
types.

Example
=======

A site keeps its main project list to the running projects, whatever state the
editor or the visitor chose, and leaves the selections made by hand alone:

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

The state select of the form then still offers :guilabel:`All` and
:guilabel:`Completed`, which change nothing. Switch off
:guilabel:`Show active state options` in the content elements the listener
acts on, or check the content element in the listener, through the record on
:php:`getContentObjectRenderer()` of the context.

..  _developers-project-events-rules:

Rules and pitfalls
==================

..  warning::

    **A demand listener widens as easily as it narrows.** The demand *is* the
    query here, not a constraint added to one, and almost nothing guards it:
    :php:`setShowHiddenRecords(true)` shows hidden project pages to every
    visitor, :php:`setPages([])` drops the storage restriction the editor chose
    in the content element, and :php:`setSorting()` overrides the editor's
    ordering for every element that renders, though only for a value that is
    one of the :php:`SortingOptions` constants, since anything else is ignored
    without a word. Two listeners that disagree are resolved by the order they
    run in, and the last one wins.

    What a listener cannot undo: the page type is pinned unconditionally,
    :php:`showHiddenRecords` reaches the :php:`disabled` flag alone (start- and
    endtime, :php:`fe_group` and :php:`deleted` stay in effect), and a
    :php:`uid` tiebreaker is always appended to the ordering.

**A replaced demand starts from the defaults.** :php:`setDemand()` is there for
a listener that builds its own demand, and such a demand carries none of what
the plugin put in the one it was handed: the :php:`showHiddenRecords` choice of
the editor, and the three the visitor can set themselves through the filter
form, the :php:`sorting`, the :php:`activeState` and the
:php:`filterCollection`, so a replacement resets the ordering, the state
filter and the category filter under them. :php:`showSelected` has to travel
with :php:`pages` or it changes what they mean: the repository reads
:php:`pages` as the selected project uids when :php:`showSelected` is true and
as storage page uids when it is false, so a single-selection element whose
demand is replaced without it turns into a storage-folder restriction. Mutate
the demand where that is enough, and carry :php:`getPages()`,
:php:`getShowSelected()`, :php:`getShowHiddenRecords()`,
:php:`getActiveState()`, :php:`getFilterCollection()` and :php:`getSorting()`
over where it is not.

**A category a listener adds is shown as an active filter.** The active filter
tags of :ref:`configuration-active-filters` read the demand the list was
queried with. A category a listener adds to every request is a tag the visitor
cannot remove, since the listener adds it again on the page the tag leads to.
The same holds for a state other than `all`.

**A field the project adds to the filter form does not reach the event.** The
redirect after a submission carries the selection the plugin accepted and
nothing else, so a listener cannot read a field of its own from the request
that follows.

Introduced in
=============

*   :ref:`feature-list-plugin-events` added the event in 3.0.
*   :ref:`breaking-1791043407` made the controller final and names the event
    as the replacement of a subclass that changed the filter, the state or the
    sorting.
*   :ref:`feature-1790226101` answers a filter submission with a redirect, so
    the event fires on the request that follows it.
