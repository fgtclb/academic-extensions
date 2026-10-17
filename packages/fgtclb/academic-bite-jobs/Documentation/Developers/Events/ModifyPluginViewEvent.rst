..  _developers-event-modify-plugin-view-event:

=====================
ModifyPluginViewEvent
=====================

:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` is owned by
:guilabel:`academic_base`, which describes it for the seven academic
extensions whose plugins dispatch it in :ref:`its developer chapter
<fgtclb/academic-base:developers-event-modify-plugin-view-event>`.
This page says what the job list of :guilabel:`academic_bite_jobs` hands to
it.

..  _developers-event-modify-plugin-view-event-dispatched:

When it is dispatched
=====================

The :ref:`Job List b-ite <integrator-job-list-bite>` dispatches the event once
each time it renders, after the request and the result event ran and the
postings are assigned to the view. The context names the extension
`AcademicBiteJobs`, the plugin `List` and the action `list`.

The view holds :html:`{jobs}`, the postings after the result listeners and the
limit, :html:`{data}`, the content element as an array, and :html:`{record}`,
the content element as a record. :html:`{settings}` carries the view of the
content element already normalised, see :confval:`Select view
<job-list-bite-view>`.

..  _developers-event-modify-plugin-view-event-api:

What this extension passes in
=============================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the job list: the request, the site and its
            language, the content object, the plugin and action name and the
            settings of the plugin, TypoScript included.
    *   -   :php:`getView()`
        -   The view the job list renders. A variable a listener assigns to it
            reaches the template and its partials.

..  _developers-event-modify-plugin-view-event-example:

Example: when the postings were fetched
=======================================

The job list is cached with its page, so a visitor may see postings that were
fetched hours ago. A site states the time of the request below the list. The
listener runs when the page is rendered for the cache, which is when the
postings were asked for, and hands that time to the view:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddFetchTimeToBiteJobList.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Context\Context;

    final class AddFetchTimeToBiteJobList
    {
        public function __construct(
            private readonly Context $context,
        ) {}

        #[AsEventListener(identifier: 'my-extension/add-fetch-time-to-bite-job-list')]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $actionContext = $event->getPluginControllerActionContext();
            if ($actionContext->getControllerExtensionName() !== 'AcademicBiteJobs') {
                return;
            }
            $event->getView()->assign(
                'postingsFetchedAt',
                $this->context->getPropertyFromAspect('date', 'full'),
            );
        }
    }

An override of :file:`Templates/BiteJobs/List.html` renders it, see
:ref:`templates`:

..  code-block:: html

    <p class="ace-note">
        As of <f:format.date format="d.m.Y H:i">{postingsFetchedAt}</f:format.date>
    </p>

..  _developers-event-modify-plugin-view-event-rules:

Rules and pitfalls
==================

**Change the postings in the result event.** A listener that replaces
:html:`{jobs}` here works around the limit of the content element, and the
postings it assigns reach no other listener. Remove, sort and enrich them in
:ref:`ModifyBiteJobPostingsEvent
<developers-event-modify-bite-job-postings-event>`.

**The event runs for the cache.** Like the request and the result event, it
runs when the page is rendered, not for every visitor of a cached page.

..  _developers-event-modify-plugin-view-event-introduced:

Introduced in
=============

*   :ref:`feature-plugin-view-event`
*   :ref:`breaking-1791043409`: the controller is final, a project assigns
    further view variables with this event instead of a subclass.
