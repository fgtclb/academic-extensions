..  _developers:

==============
For developers
==============

Both project list content elements, :guilabel:`Projects` and
:guilabel:`Selected Projects`, dispatch two PSR-14 events of this extension
and the plugin view event of :guilabel:`academic_base`. They are the supported
way to change what a plugin queries and what it renders, and they replace the
only alternative there used to be: subclassing :php:`ProjectController` and
re-registering the plugin.

Which classes of this extension are public API, and what that promises, is
stated for all academic extensions on the :ref:`extension points page of
academic_base <fgtclb/academic-base:developers-extension-points>`.

Nothing changes in an installation that has no listener: both plugins query and
render exactly what they did before.

..  _developers-project-events:

Events
======

..  list-table::
    :header-rows: 1
    :widths: 25 30 30 15

    *   -   Event
        -   Dispatched
        -   What a listener can change
        -   Owner
    *   -   :ref:`ModifyProjectDemandEvent <developers-event-modify-project-demand-event>`
        -   by both content elements, after the demand is built from the
            content element settings and the request and before the projects
            are queried
        -   replace the demand
        -   this extension
    *   -   :ref:`ModifyProjectListEvent <developers-event-modify-project-list-event>`
        -   by both, after the query and before the view variables are
            assigned
        -   replace the projects, replace the applicable categories, assign
            further view variables
        -   this extension
    *   -   :ref:`ModifyPluginViewEvent <developers-event-modify-plugin-view-event>`
        -   by both, once each time they render, after the action assigned its
            own variables
        -   assign further view variables
        -   :guilabel:`academic_base`

One action, :php:`ProjectController::listAction()`, serves both content
elements, so all three events fire for both, in this order. Which one is
rendering is on the context every event carries, as the plugin name
:php:`ProjectList` or :php:`ProjectListSingle`.

..  toctree::
    :hidden:
    :titlesonly:
    :glob:

    Events/*
