.. _important-1787054400:

================================================================================
Important: The content element wizard entry is registered for every installation
================================================================================

Description
===========

The new content element wizard entry of this extension is defined in
:file:`Configuration/TsConfig/Wizards/NewContentElement.tsconfig`, and that file
was imported only by :file:`Configuration/Sets/AcademicBiteJobs/page.tsconfig`.

:file:`Configuration/page.tsconfig` of an extension is auto-included for the
whole installation since TYPO3 v12.0 (Feature: #96614); a site set is opt-in per
site. This extension shipped no such file at all — the only one of this set that
ships page TSconfig and does not — so on a site that does not enable the set
:yaml:`fgtclb/academic-bite-jobs` its page TSconfig was never read.

On TYPO3 v12 the loss is total rather than cosmetic. Site sets do not exist on
that version at all — they arrived in v13.1 (Feature: #103437) — so there was no
delivery path for the wizard entry whatsoever, and v12 builds the wizard from
page TSconfig alone, the TCA based registration having arrived in v13.0
(Feature: #102834). The plugin was therefore missing from
:guilabel:`New content element` on every TYPO3 v12 installation, and a content
element of this type could only be created by inserting a different one and
changing its type afterwards.

The extension now ships :file:`Configuration/page.tsconfig` with the import, and
the copy in the site set was removed rather than left to be applied twice.

Impact
======

On TYPO3 v12 the plugin is offered by :guilabel:`New content element`, in the
:guilabel:`Academic` group, where it was absent before.

Nothing changes on TYPO3 v13. The wizard is built from TCA there
(Feature: #102834), so the entry for :typoscript:`academicbitejobs_list` is
offered from :file:`Configuration/TCA/Overrides/tt_content.php` whether or not
the page TSconfig is read, and the :typoscript:`show := addToList()` line the
file also carries has no reader left in :php:`NewContentElementController`.

Affected Installations
======================

All installations of this extension. Nothing has to be done: existing content
elements are untouched, only the way a new one is created changes.

.. index:: TSConfig, Backend, ext:academic_bite_jobs
