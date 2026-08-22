.. _important-1787054400:

===================================================================
Important: The extension ships page TSconfig for every installation
===================================================================

Description
===========

The page TSconfig of this extension used to be reachable through the site set
:yaml:`fgtclb/academic-bite-jobs` and through nothing else: the new content
element wizard entry lived in
:file:`Configuration/TsConfig/Wizards/NewContentElement.tsconfig`, and the only
file importing it was :file:`Configuration/Sets/AcademicBiteJobs/page.tsconfig`.
On a site that did not enable that set, the page TSconfig of this extension was
never read at all.

On TYPO3 v12 that loss was total rather than cosmetic. Site sets do not exist on
that version — they arrived in v13.1 (Feature: #103437) — so there was no
delivery path for the page TSconfig whatsoever, and v12 builds the new content
element wizard from page TSconfig alone, the TCA based registration having
arrived in v13.0 (Feature: #102834). The plugin was therefore missing from
:guilabel:`New content element` on every TYPO3 v12 installation, and a content
element of this type could only be created by inserting a different one and
changing its type afterwards.

:file:`Configuration/page.tsconfig` of an extension is auto-included for the
whole installation since TYPO3 v12.0 (Feature: #96614), and this extension
shipped no such file — the only one of these extensions that ships page TSconfig
and did not. It ships one now, so what has to hold on every installation no
longer depends on the site configuration.

What that file carries is the hide-by-default half of the configuration: it
removes :typoscript:`academicbitejobs_list` from the selectable content element
types for the whole installation. The wizard entry and the matching re-enable
moved to :file:`Configuration/TSconfig/List/page.tsconfig`, which a site reaches
through the site set :yaml:`fgtclb/academic-bite-jobs-list` on TYPO3 v13, or
through the page field :guilabel:`Page TSconfig` on either version — so the
content element is offered where it is wanted rather than everywhere, and the
decision no longer depends on whether a site happens to enable a set.

Impact
======

On TYPO3 v12 the plugin is offered by :guilabel:`New content element`, in the
:guilabel:`Academic` group, on every page that includes the page TSconfig of the
component — where it was offered nowhere at all before. The
:typoscript:`show := addToList()` line that makes it appear there moved into
that same file, so it arrives together with the element definition.

On TYPO3 v13 the wizard entry is not what makes the content element selectable:
the wizard is built from TCA since Feature: #102834, and the version drops an
element whose value appears in :typoscript:`TCEFORM.tt_content.CType.removeItems`
before rendering.

Where the content element is offered, and what an installation has to do about
it, is described in
:ref:`breaking-site-sets-and-static-templates-restructured`.

Affected Installations
======================

All installations of this extension. What to do is in the Breaking entry
referenced above; this entry only records that the extension now ships a page
TSconfig file that is read without any site configuration.

.. index:: TSConfig, Backend, ext:academic_bite_jobs
