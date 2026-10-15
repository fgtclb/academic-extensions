..  _developers:

==============
For developers
==============

The job list asks one service for its postings, and that service dispatches two
PSR-14 events: one before the request is sent to the B-ITE API, one after the
response is decoded. They are the supported way to filter by a B-ITE custom
field, to ask for another locale, or to remove, enrich and group the postings,
without copying the service or the controller. When the job list renders, it
dispatches the plugin view event of :guilabel:`academic_base` as well.

Which classes of this extension are public API, and what that promises, is
stated for all academic extensions on the `extension points page of
academic_base <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/ExtensionPoints/Index.html>`__.

..  _developers-events:

Events
======

The three events run in this order, once each time a job list renders.

..  list-table::
    :header-rows: 1
    :widths: 25 30 30 15

    *   -   Event
        -   Dispatched
        -   What a listener can change
        -   Owner
    *   -   :ref:`ModifyBiteJobPostingsRequestEvent
            <developers-event-modify-bite-job-postings-request-event>`
        -   by the job list, after the request is built from the options of
            the content element and before it is sent
        -   change the payload of the request: the filter, the channel, the
            locale, the sorting, the paging
        -   this extension
    *   -   :ref:`ModifyBiteJobPostingsEvent
            <developers-event-modify-bite-job-postings-event>`
        -   by the job list, after the response is decoded, also after a
            failed request, and before the limit is applied
        -   remove, change, add and enrich postings
        -   this extension
    *   -   :ref:`ModifyPluginViewEvent
            <developers-event-modify-plugin-view-event>`
        -   by the job list, after it assigned the postings to its view
        -   assign further view variables
        -   :guilabel:`academic_base`

..  _developers-rules:

Rules and pitfalls
==================

These apply to both events of this extension.

**Two kinds of settings.** :php:`getSettings()` returns what the content element
stores below :typoscript:`settings.jobs`, exactly as it is stored: without the
TypoScript of the plugin, and with the view value of a content element saved
with version 2.0 as it was saved. The settings the job list works with, the
TypoScript :typoscript:`settings.jobs.groupBy` included, are those of the plugin
action context, :php:`$event->getPluginControllerActionContext()?->getSettings()`.

**The events run when the page is rendered, not per visitor.** A page is cached
with the postings the listeners handed back, so a listener cannot show a
different list to different visitors of a cached page.

**A listener is not guarded.** An exception a listener throws reaches the
visitor, as any error of project code does. A payload that cannot be encoded as
JSON is logged like a failed request, and the job list renders the postings the
result listeners hand back.

**Two job lists on a page are two requests.** Each job list sends its own
request and dispatches both events once. A job list whose request fails renders
no postings, whatever another job list on the page received.

..  toctree::
    :hidden:
    :titlesonly:

    Events/ModifyBiteJobPostingsRequestEvent
    Events/ModifyBiteJobPostingsEvent
    Events/ModifyPluginViewEvent
