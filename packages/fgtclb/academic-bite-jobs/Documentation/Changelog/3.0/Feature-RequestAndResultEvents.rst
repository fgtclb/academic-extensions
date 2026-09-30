..  _feature-1790774400:

=========================================================
Feature: Request and result events for the B-ITE job list
=========================================================

Description
===========

The job list sends its request to the B-ITE API through a service that now
dispatches two PSR-14 events:

*   :php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent`, before the
    request is sent. A listener changes the payload: the filter, the channel,
    the locale, the sorting, the paging, or any other key the B-ITE search API
    accepts.
*   :php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent`, after the
    response is decoded, and after a failed request as well. A listener
    removes, changes or adds postings, or adds a value to every posting that
    the list is grouped by.

Both events hand a listener the plugin settings of the content element, the
request and the context of the job list. The limit of the content element is
applied to the postings the listeners hand back.

This is the API the removal of the project specific custom fields in version
2.1 announced, see :ref:`breaking-1758798000`. The events, the payload keys and
an example that filters by a custom field and groups by it are described in
:ref:`developers`.

Impact
======

A project filters the job list by a B-ITE custom field, asks for another
locale, or enriches and groups the postings with event listeners instead of a
copy of the extension. Without a listener, the request and the rendered list
stay the same. The behaviour is the same on TYPO3 v13 and v14.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_bite_jobs
