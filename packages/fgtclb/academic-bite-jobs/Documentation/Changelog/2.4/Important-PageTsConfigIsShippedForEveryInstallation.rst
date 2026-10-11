.. _important-1787054400:

===================================================================
Important: The extension ships page TSconfig for every installation
===================================================================

Description
===========

Up to version 2.3, the page TSconfig of this extension was reachable through the
site set :yaml:`fgtclb/academic-bite-jobs` and through nothing else. The new
content element wizard entry lived in
:file:`Configuration/TsConfig/Wizards/NewContentElement.tsconfig`, and the only
file importing it was :file:`Configuration/Sets/AcademicBiteJobs/page.tsconfig`.
On a site that did not enable that set, the page TSconfig of this extension was
not read at all.

On TYPO3 v12 that meant the wizard entry was never read. Site sets arrived in
TYPO3 v13.1, and TYPO3 v12 builds the new content element wizard from page
TSconfig alone. The plugin was therefore missing from :guilabel:`New content
element` on every TYPO3 v12 installation that did not include the file itself,
and a content element of this type could only be created by inserting a
different one and changing its type afterwards.

TYPO3 reads :file:`Configuration/page.tsconfig` of every active extension for
the whole installation since v12.0. This extension now ships that file. It
carries one line, which removes :typoscript:`academicbitejobs_list` from the
selectable content element types for the whole installation.

The wizard entry and the line that makes the content element selectable again
moved to :file:`Configuration/TSconfig/List/page.tsconfig`. A site reaches that
file through the site set :yaml:`fgtclb/academic-bite-jobs-list` on TYPO3 v13,
or through the page field :guilabel:`Page TSconfig` on either version, where it
is offered as :guilabel:`Academic Bite Jobs: Job list`. The content element is
offered where that file is included, and nowhere else.

Impact
======

On TYPO3 v12 the plugin is offered by :guilabel:`New content element`, in the
:guilabel:`Academic` group, on every page that includes the page TSconfig of the
component. The :typoscript:`show := addToList()` line that makes it appear
there moved into that same file, so it arrives together with the element
definition.

On TYPO3 v13 the wizard is built from TCA, and it leaves out an element whose
value appears in :typoscript:`TCEFORM.tt_content.CType.removeItems`. There, too,
the content element is offered only on pages that include the page TSconfig of
the component.

Affected Installations
======================

All installations of this extension. Where the content element is offered, and
what an installation has to do about it, is described in
:ref:`breaking-site-sets-and-static-templates-restructured`. This entry only
records that the extension now ships a page TSconfig file that is read without
any site configuration.

.. index:: TSConfig, Backend, ext:academic_bite_jobs
