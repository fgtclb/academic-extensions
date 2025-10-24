.. include:: /Includes.rst.txt

.. _breaking-1758798000:

===============================================
Breaking: Remove project specific custom fields
===============================================

Description
===========

`EXT:academic_bite_jobs` provides flexform options
and filters based on custom project specific fields
and options not interchangeable with other projects
and not making sense being provided by default.

These custom fields needs to be setup the same way
on the external bite-job system and is mosts likely
not the way to go.

Mangling around with the current implementation even
if only the options are removed is not suitable and
the internal implementation needs to be streamlined
first before adding customization endpoints in a
reasonable way as public API.

Based on aforementioned background project specific
implementation and options are now removed to clean
up the code base as preparation to streamline first
the internal implementation with dedicated changes,
re-introducing the feature with a new API at a later
point.

This is technically breaking, at least for the one
specific project and is handled as breaking for it,
albeit it should not target any other project at all.

Reducing the feature set to the bare minimum simply
displaying only the single job detail page without
related jobs is the mostly used base ground and the
essential base feature to start fresh from.

Additionally, fluid template structure is streamlined
in the same step to follow the restructure epic for
all academic extensions.


Impact
======

Following removals are included:

* `flexform` options `settings.jobs.custom.zuordnung` and
 `settings.jobs.groupBy` are removed.
* `BiteFieldsHelper` is removed being unused and an empty class anyway.
* `BiteJobsService->fetchBiteJobs()` handling code for removed flexform
  setting options is removed from method.
* `public BiteJobsService->findCustomJobRelations()` is removed.
* `public BiteJobsService->findCustomBiteFieldLabelsFromOptions()` is removed.
* `public BiteJobsService->groupByRelations()` is removed.
* `public BiteJobsService->mapFieldsToJobs()` is removed.

Affected Installations
======================

EXT:academic_bite_jobs before v2.1 installations using custom project specific
fields and options

Migration
=========

[TODO] How to migrate the breaking change?

.. index:: FlexForm, Fluid, Frontend
