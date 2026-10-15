.. _important-projects-without-end-date-are-active:

======================================================
Important: Projects without an end date are now active
======================================================

Description
===========

The end date of a project page is stored in the column
`tx_academicprojects_end_date`, which allows `NULL`. A page keeps `NULL` there
as long as no end date was ever stored for it: a page created in the page
tree, a page switched to the project page type, an imported page or one from
before the extension was installed. Saving such a page in the backend without
entering an end date does not change that.

The :guilabel:`Active` filter of the project lists left these projects out,
and so did the :guilabel:`Completed` filter, so a list filtered either way
never showed them, although they are the open ended projects.

A project without a stored end date is now listed by the :guilabel:`Active`
filter, exactly like a project whose end date was saved empty. The
:guilabel:`Completed` filter is unchanged and never lists it.

Impact
======

A list a visitor filters by :guilabel:`Active`, and a :guilabel:`Projects` or
:guilabel:`Projects (selected)` content element whose :guilabel:`Active state`
is set to :guilabel:`Active`, shows these projects from now on. In a paginated
list, entries can move to a later page.

No stored value changes, and there is no upgrade wizard to run.

.. index:: Frontend, ext:academic_projects
