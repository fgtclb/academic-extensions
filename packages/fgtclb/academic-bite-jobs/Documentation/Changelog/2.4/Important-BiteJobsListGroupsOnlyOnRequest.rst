..  _important-1789660801:

========================================================================
Important: The job list groups only on request and reads old view values
========================================================================

Description
===========

Version 2.1 removed the grouping setting of the :guilabel:`Job list` content
element and renamed its view values, but the list template and the stored
content elements were not adapted. Since then:

*   every job list was rendered as one unnamed group, so every job title was one
    heading level lower than the header layout of the content element asks for —
    an `<h3>` instead of an `<h2>` for layout 1, for example;
*   a content element saved with 2.0 stores `ListView`, `CardView` or
    `TableView`, names a partial that does not exist and fails to render. So
    does a content element without a view value.

The job list is now grouped only when TypoScript names a grouping field:

..  code-block:: typoscript

    plugin.tx_academicbitejobs.settings.jobs.groupBy = department

The value is a field of the job postings the B-ITE API answers. Without it, the
list renders ungrouped, as the 2.1 breaking change intended.

The stored view values `ListView`, `CardView` and `TableView` render the list,
card and table view, and an empty or unknown value renders the list view. The
value is normalised before the view is rendered, so a template override that
reads `{settings.jobs.view}` receives `List`, `Card` or `Table`.

The upgrade wizard :guilabel:`Migrate the plugin settings of academic_bite_jobs
job lists stored before 2.1.` (identifier
`academicBiteJobs_listViewFlexFormUpgradeWizard`) rewrites the stored view value
of every job list content element, hidden ones included, to `List`, `Card` or
`Table`.

It also removes the two settings version 2.1 removed from the plugin,
`settings.jobs.groupBy` and `settings.jobs.custom.zuordnung`. They stayed in the
stored FlexForms, and a stored grouping setting is still merged into the plugin
settings — so until the wizard has run, a content element saved with 2.0 and the
grouping option :guilabel:`thema` keeps being grouped by a field the job
postings no longer carry. Every other setting is left as it is.

Impact
======

Job titles of every job list that does not configure a grouping field move up
one heading level. Content elements saved with 2.0 render again — those that
stored the grouping option of 2.0 once the upgrade wizard has run.

Affected Installations
======================

Every installation using the :guilabel:`Job list` content element. Installations
with content elements saved with 2.0 are additionally affected by the view
values.

Content elements created with 1.x are a plugin of the list content type and are
not rendered by this version at all; neither the list nor the upgrade wizard
covers them.

Migration
=========

Run the upgrade wizard after the update. It makes the backend form of old
content elements show the view the frontend renders, and it removes the
grouping setting of 2.0, which would otherwise keep those lists grouped. Until
it has run, the frontend reads the old view values anyway.

Check the heading styles of job lists. An installation that relied on the
lower heading level adapts its styles or overrides the
:file:`Partials/BiteJobs/Header.html` partial.

An installation that adds a grouping field to the job postings sets
`plugin.tx_academicbitejobs.settings.jobs.groupBy` to that field.

..  index:: Fluid, FlexForm, Frontend, TypoScript, NotScanned
