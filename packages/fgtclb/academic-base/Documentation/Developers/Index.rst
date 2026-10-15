..  _developers:

==============
For developers
==============

What the academic extensions offer to the code of a project, and what they
keep to themselves.

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`Extension points <developers-extension-points>`

        The public API of every academic extension and of
        :guilabel:`category_types`: the events, the interfaces, the domain
        models, and what an XCLASS or a subclass may and may not rely on.

    ..  card:: :ref:`Events <developers-events>`

        The three events of this extension: one it dispatches itself, and two
        it owns for the other academic extensions to dispatch.

..  _developers-events:

Events
======

This extension owns three PSR-14 events. It dispatches one of them itself.
The other two are dispatched by the plugins and the backend item providers of
the other academic extensions, which is why they live here: one event class
serves all of them, and a listener written once applies to every extension
that dispatches it. The pages below describe each event in general. The
developer chapter of every dispatching extension describes what that
extension passes in, with an example that fits its plugins.

..  list-table::
    :header-rows: 1
    :widths: 25 30 30 15

    *   -   Event
        -   Dispatched
        -   What a listener can change
        -   Owner
    *   -   :ref:`CollectFrontendIconsEvent
            <developers-event-collect-frontend-icons-event>`
        -   by this extension, once each time the frontend icon registry is
            built, when the system caches are built or warmed up, not per
            request
        -   contribute icons it computes in code
        -   this extension
    *   -   :ref:`ModifyPluginViewEvent
            <developers-event-modify-plugin-view-event>`
        -   by the plugins of seven other academic extensions, once each time
            an action renders its view, after the action assigned its own
            variables
        -   assign further view variables
        -   this extension
    *   -   :ref:`ModifyTcaSelectFieldItemsEvent
            <developers-event-modify-tca-select-field-items-event>`
        -   by the item providers of :guilabel:`academic_jobs`,
            :guilabel:`academic_persons` and
            :guilabel:`academic_contacts4pages`, when the items of one of their
            select fields are built, in the backend form and in the job form
        -   add, remove and reorder the items
        -   this extension

Every event is public API, see :ref:`developers-extension-points-events`.

..  toctree::
    :maxdepth: 2
    :titlesonly:
    :hidden:

    ExtensionPoints/Index
    Events/CollectFrontendIconsEvent
    Events/ModifyPluginViewEvent
    Events/ModifyTcaSelectFieldItemsEvent
