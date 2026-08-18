.. _important-1787054400:

================================================================================
Important: The content element wizard entry is registered for every installation
================================================================================

Description
===========

The new content element wizard entry of this extension is defined in
:file:`Configuration/TSconfig/Wizards/NewContentElement.tsconfig`, and that file
was imported only by :file:`Configuration/Sets/AcademicBiteJobs/page.tsconfig`.

:file:`Configuration/page.tsconfig` of an extension is auto-included for the
whole installation since TYPO3 v12.0 (Feature: #96614); a site set is opt-in per
site. This extension shipped no such file at all — the only one of this set that
ships page TSconfig and does not — so on a site that does not enable the set
:yaml:`fgtclb/academic-bite-jobs` its page TSconfig was never read.

The extension now ships :file:`Configuration/page.tsconfig` with the import, and
the copy in the site set was removed rather than left to be applied twice.

Impact
======

Nothing changes on TYPO3 v13 and v14. The new content element wizard is built
from TCA on both of them (Feature: #102834), so the entry for
:typoscript:`academicbitejobs_list` is offered from
:file:`Configuration/TCA/Overrides/tt_content.php` whether or not the page
TSconfig is read, and the :typoscript:`show := addToList()` line the file also
carries has no reader left in :php:`NewContentElementController`.

What changes is that the page TSconfig of this extension no longer depends on
the site configuration to be delivered, which is what an installation has to be
able to rely on the moment the file carries anything the TCA registration does
not cover.

Affected Installations
======================

All installations of this extension. Nothing has to be done.

.. index:: TSConfig, Backend, ext:academic_bite_jobs
