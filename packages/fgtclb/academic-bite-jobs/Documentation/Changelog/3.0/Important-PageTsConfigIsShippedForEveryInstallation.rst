.. _important-1787054400:

===================================================================
Important: The extension ships page TSconfig for every installation
===================================================================

Description
===========

The page TSconfig of this extension used to be reachable through the site set
:yaml:`fgtclb/academic-bite-jobs` and through nothing else: the new content
element wizard entry lived in
:file:`Configuration/TSconfig/Wizards/NewContentElement.tsconfig`, and the only
file importing it was :file:`Configuration/Sets/AcademicBiteJobs/page.tsconfig`.
On a site that did not enable that set, the page TSconfig of this extension was
never read at all.

:file:`Configuration/page.tsconfig` of an extension is auto-included for the
whole installation since TYPO3 v12.0 (Feature: #96614), and this extension
shipped no such file — the only one of these extensions that ships page TSconfig
and did not. It ships one now, so what has to hold on every installation no
longer depends on the site configuration.

What that file carries is the hide-by-default half of the configuration: it
removes :typoscript:`academicbitejobs_list` from the selectable content element
types for the whole installation. The wizard entry and the matching re-enable
moved to :file:`Configuration/TSconfig/List/page.tsconfig`, which a site reaches
through the site set :yaml:`fgtclb/academic-bite-jobs-list` or through the page
field :guilabel:`Page TSconfig` — so the content element is offered where it is
wanted rather than everywhere, and the decision no longer depends on whether a
site happens to enable a set.

Impact
======

The wizard entry itself is unchanged in content, and on TYPO3 v13 and v14 it is
not what makes the content element selectable: the wizard is built from TCA
since Feature: #102834, and both versions drop an element whose value appears in
:typoscript:`TCEFORM.tt_content.CType.removeItems` before rendering. The
:typoscript:`show := addToList()` line the file also carries has no reader left
in :php:`NewContentElementController` on either version; it is kept because it
still describes the intent of the file.

Where the content element is offered, and what an installation has to do about
it, is described in
:ref:`breaking-site-sets-and-static-templates-restructured`.

Affected Installations
======================

All installations of this extension. What to do is in the Breaking entry
referenced above; this entry only records that the extension now ships a page
TSconfig file that is read without any site configuration.

.. index:: TSConfig, Backend, ext:academic_bite_jobs
