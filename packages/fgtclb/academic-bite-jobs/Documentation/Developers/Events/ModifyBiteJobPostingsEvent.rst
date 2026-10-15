..  _developers-event-modify-bite-job-postings-event:
..  _developers-result-event:

==========================
ModifyBiteJobPostingsEvent
==========================

:php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent` is owned by
this extension. A listener removes, changes, adds or enriches the postings the
job list renders.

..  _developers-event-modify-bite-job-postings-event-dispatched:

When it is dispatched
=====================

The :ref:`Job List b-ite <integrator-job-list-bite>` dispatches the event each
time it renders, after the response of B-ITE is decoded and before
:confval:`Maximum number of job offers <job-list-bite-limit>` is applied. It is
dispatched after a failed request as well, then with no postings and no response
data, so a listener does not need to know how the request went. The
:ref:`request event <developers-event-modify-bite-job-postings-request-event>`
comes before it.

..  _developers-event-modify-bite-job-postings-event-api:

What a listener reads and changes
=================================

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

..  _developers-example-grouping:

Example: a grouping by a custom field
=====================================

The second half of the :ref:`custom field example <developers-example>`
writes the name of the relation into every posting, so the job list can group
by it:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/NameTheRelationOfEveryJob.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class NameTheRelationOfEveryJob
    {
        private const RELATION_NAMES = [
            '01' => 'Appointment procedures',
            '02' => 'Academic staff',
            '03' => 'Non-scientific staff',
            '04' => 'Training positions',
        ];

        #[AsEventListener(identifier: 'my-extension/name-the-relation-of-every-job')]
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

The grouping itself is TypoScript, see :ref:`configuration-general-group-by`:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academicbitejobs.settings.jobs.groupBy = relationName

Where the custom field sits in a posting, and which values it has, depends on
how it is set up in B-ITE, so read one response of your job listing before
relying on the path above. A listener that needs the labels of the values can
ask the options API of B-ITE for them.

..  _developers-event-modify-bite-job-postings-event-rules:

Rules and pitfalls
==================

**Hand back a list.** :php:`setJobPostings()` throws an
:php:`\InvalidArgumentException` for an array that is not keyed from `0`
without gaps, or for a posting that is not an array. A listener that removes
postings with :php:`array_filter()` passes the result through
:php:`array_values()`.

**The templates read the keys B-ITE answers with.** The views show the `title`,
the `url` and the `endsOn` of a posting. A posting a listener adds needs them
too, and a value a listener adds appears only where a template override or the
grouping reads it.

The rules both events share are on the :ref:`overview page
<developers-rules>`.

..  _developers-event-modify-bite-job-postings-event-introduced:

Introduced in
=============

*   :ref:`feature-1790774401`: the event, in version 2.4.
*   :ref:`breaking-1791043409`: the controller is final, a project changes the
    postings with this event instead of a subclass.
