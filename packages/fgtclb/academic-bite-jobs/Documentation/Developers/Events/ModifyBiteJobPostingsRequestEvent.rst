..  _developers-event-modify-bite-job-postings-request-event:
..  _developers-request-event:

=================================
ModifyBiteJobPostingsRequestEvent
=================================

:php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent` is
owned by this extension. A listener changes the request the job list sends to
the search API of B-ITE.

..  _developers-event-modify-bite-job-postings-request-event-dispatched:

When it is dispatched
=====================

The :ref:`Job List b-ite <integrator-job-list-bite>` dispatches the event each
time it renders, after the request is built from the options of the content
element and before it is sent. The payload a listener hands back is sent as it
is, encoded as JSON. The :ref:`result event
<developers-event-modify-bite-job-postings-event>` follows once the response
is decoded.

..  _developers-event-modify-bite-job-postings-request-event-api:

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getPayload()`, :php:`setPayload()`
        -   The request sent to the B-ITE search API, see the keys below.
    *   -   :php:`getSettings()`
        -   The values stored below :typoscript:`settings.jobs` in the plugin
            settings of the content element, which the payload is built from.
            They are not merged with TypoScript and not normalised, see
            :ref:`developers-rules`.
    *   -   :php:`getRequest()`
        -   The request of the page being rendered.
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the job list that asked, with all its settings,
            TypoScript included, or :php:`null` when the service was called
            outside of a plugin.

..  _developers-request-payload:

The keys of the payload
-----------------------

The extension sends these keys. A listener changes any of them and adds every
other key the B-ITE search API accepts. Renaming or removing one of them in a
later version is a breaking change.

..  list-table::
    :header-rows: 1

    *   -   Key
        -   Sent by the extension
    *   -   `key`
        -   The job listing key of the content element,
            :confval:`Job listing key <job-list-bite-job-listing-key>`.
    *   -   `channel`
        -   `0`.
    *   -   `locale`
        -   `de`.
    *   -   `page`
        -   `offset` `0`: the postings from the first one on.
    *   -   `filter`
        -   An empty filter: every posting of the job listing.
    *   -   `sort`
        -   `order` and `by`, the sort direction and the sort field of the
            content element, :confval:`Sort direction
            <job-list-bite-sorting-direction>` and :confval:`Sort by
            <job-list-bite-sort-by>`.

..  _developers-example:

Example: a custom field filter
==============================

Up to version 2.0 the extension filtered by the B-ITE custom field `zuordnung`
of one installation and grouped the postings by its value. Version 2.1 removed
that code, see :ref:`breaking-1758798000`. Two listeners bring it back for the
installation that needs it, and only there. This one adds the filter to the
request, the second one names the value of every posting for the grouping, see
:ref:`developers-example-grouping`.

The value of the filter comes from a plugin setting of the project's own
FlexForm, here `settings.jobs.relation`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/FilterJobsByRelation.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class FilterJobsByRelation
    {
        #[AsEventListener(identifier: 'my-extension/filter-jobs-by-relation')]
        public function __invoke(ModifyBiteJobPostingsRequestEvent $event): void
        {
            $relation = (string)($event->getSettings()['relation'] ?? '');
            if ($relation === '' || $relation === 'all') {
                return;
            }
            $payload = $event->getPayload();
            $payload['filter'] = ['custom.zuordnung' => ['in' => [$relation]]];
            $event->setPayload($payload);
        }
    }

Where the custom field sits in a posting, and which values it has, depends on
how it is set up in B-ITE, so read one response of your job listing before
relying on the path above.

The upgrade wizard `academicBiteJobs_listViewFlexFormUpgradeWizard` removes the
setting `settings.jobs.custom.zuordnung` of version 2.0 from every job list, so
a project FlexForm that brings the field back gives it a name of its own, as
`settings.jobs.relation` above.

..  _developers-event-modify-bite-job-postings-request-event-rules:

Rules and pitfalls
==================

**The payload is sent as the listeners leave it.** Nothing checks it against
the B-ITE API. A payload B-ITE rejects makes the request fail, the failure is
logged, and the list shows its empty state, unless a result listener adds
postings.

**The options of the content element are already in the payload.** A listener
that replaces `sort` or `key` overrides what the editor chose, so change them
only where a project setting of its own asks for it.

The rules both events share are on the :ref:`overview page
<developers-rules>`.

..  _developers-event-modify-bite-job-postings-request-event-introduced:

Introduced in
=============

*   :ref:`feature-1790774401`: the event, in version 2.4.
*   :ref:`breaking-1791043409`: the controller is final, a project changes the
    request with this event instead of a subclass.
