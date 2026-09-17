..  index:: Configuration
..  _configuration-general:

=====================
General configuration
=====================

..  _configuration-general-view:

The view of the job list
========================

The plugin settings of a :guilabel:`Job list` content element select one of
three views: :guilabel:`List`, :guilabel:`Card` or :guilabel:`Table`. The stored
values are `List`, `Card` and `Table`.

Content elements saved with version 2.0 store `ListView`, `CardView` or
`TableView`. They render the corresponding view, and a content element with an
empty or unknown value renders the list view. Templates always receive the
normalised value in `{settings.jobs.view}`.

The upgrade wizard `academicBiteJobs_listViewFlexFormUpgradeWizard` rewrites the
stored values of existing content elements, so their backend form shows the view
the frontend renders.

..  _configuration-general-group-by:

Grouping the jobs
=================

The job list renders the jobs ungrouped. To group them, name a field of the job
postings in TypoScript:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academicbitejobs.settings.jobs.groupBy = department

The list then renders one group per value of that field, headed by the value,
and every job title one heading level below the group heading. The field has to
be part of the job postings the B-ITE API answers for the job listing key of the
content element; it is not offered in the plugin settings. An empty value, or
`none`, switches the grouping off again.

A content element saved before version 2.1 may still store a grouping setting of
its own, which wins over the TypoScript. The upgrade wizard
`academicBiteJobs_listViewFlexFormUpgradeWizard` removes it.
