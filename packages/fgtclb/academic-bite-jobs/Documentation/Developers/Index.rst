..  _developers:

==============
For developers
==============

The job list asks one service for its postings, and that service dispatches two
PSR-14 events: one before the request is sent to the B-ITE API, one after the
response is decoded. They are the supported way to filter by a B-ITE custom
field, to ask for another locale, or to remove, enrich and group the postings,
without copying the service or the controller.

Both events are public API: their class names, their methods and the payload
keys listed below stay as they are within version 2. The service that
dispatches them is not, so replace neither the service nor the controller.

..  _developers-request-event:

The request event
=================

:php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent` is dispatched
each time a job list renders, after the request is built from the plugin
settings and before it is sent. The payload a listener hands back is sent as it
is, encoded as JSON.

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
            below.
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
        -   The job listing key of the content element.
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
            content element.

..  _developers-result-event:

The result event
================

:php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent` is dispatched
each time a job list renders, after the response is decoded. It is dispatched
after a failed request as well, then with no postings and no response data, so a
listener does not need to know how the request went.

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getJobPostings()`, :php:`setJobPostings()`
        -   The postings the job list renders, a list with one array per
            posting as B-ITE answers it. The setter refuses anything else.
    *   -   :php:`getResponseData()`
        -   The decoded response of the B-ITE API, or an empty array when the
            request failed or the response was not JSON.
    *   -   :php:`getSettings()`
        -   The values stored below :typoscript:`settings.jobs` in the plugin
            settings of the content element, as in the request event.
    *   -   :php:`getRequest()`
        -   The request of the page being rendered.
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the job list that asked, as in the request event.

The limit of the content element is applied to the postings the listeners hand
back, so a listener sees every posting of the response and the job list never
renders more than the limit.

..  _developers-example:

Example: a custom field filter and a grouping
=============================================

Up to version 2.0 the extension filtered by the B-ITE custom field `zuordnung`
of one installation and grouped the postings by its value. Version 2.1 removed
that code, see :ref:`breaking-1758798000`. Two listeners bring it back for the
installation that needs it, and only there.

The first one adds the filter to the request. The value comes from a plugin
setting of the project's own FlexForm, here `settings.jobs.relation`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/FilterJobsByRelation.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent;

    final class FilterJobsByRelation
    {
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

The second one writes the name of the relation into every posting, so the job
list can group by it:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/NameTheRelationOfEveryJob.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent;

    final class NameTheRelationOfEveryJob
    {
        private const RELATION_NAMES = [
            '01' => 'Appointment procedures',
            '02' => 'Academic staff',
            '03' => 'Non-scientific staff',
            '04' => 'Training positions',
        ];

        public function __invoke(ModifyBiteJobPostingsEvent $event): void
        {
            $jobs = $event->getJobPostings();
            foreach ($jobs as $index => $job) {
                $relation = (string)($job['custom']['zuordnung'] ?? '');
                $jobs[$index]['relationName'] = self::RELATION_NAMES[$relation] ?? '';
            }
            $event->setJobPostings($jobs);
        }
    }

TYPO3 v12 has no attribute of its own to register an event listener, so both
are registered in the :file:`Services.yaml` of the extension, which works on
TYPO3 v12 and v13 alike. Do not reach for the :php:`#[AsEventListener]`
attribute of Symfony instead: TYPO3 does not read it, and the listener never
runs, without any error.

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      _defaults:
        autowire: true
        autoconfigure: true
        public: false

      MyVendor\MyExtension\:
        resource: '../Classes/*'

      MyVendor\MyExtension\EventListener\FilterJobsByRelation:
        tags:
          - name: event.listener
            identifier: 'my-extension/filter-jobs-by-relation'

      MyVendor\MyExtension\EventListener\NameTheRelationOfEveryJob:
        tags:
          - name: event.listener
            identifier: 'my-extension/name-the-relation-of-every-job'

The grouping itself is TypoScript, see :ref:`configuration-general-group-by`:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academicbitejobs.settings.jobs.groupBy = relationName

Where the custom field sits in a posting, and which values it has, depends on
how it is set up in B-ITE, so read one response of your job listing before
relying on the path above. A listener that needs the labels of the values can
ask the options API of B-ITE for them.

The upgrade wizard `academicBiteJobs_listViewFlexFormUpgradeWizard` removes the
setting `settings.jobs.custom.zuordnung` of version 2.0 from every job list, so
a project FlexForm that brings the field back gives it a name of its own, as
`settings.jobs.relation` above.

..  _developers-rules:

Rules worth knowing
===================

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
