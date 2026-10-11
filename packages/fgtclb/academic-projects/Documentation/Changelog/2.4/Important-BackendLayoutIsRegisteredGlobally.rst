..  _important-1786969200:

==================================================================
Important: The backend layout is registered for every installation
==================================================================

Description
===========

The backend layout of the page type this extension registers was imported only
by :file:`Configuration/Sets/AcademicProjects/page.tsconfig`.

:file:`Configuration/page.tsconfig` of an extension is included for the whole
installation since TYPO3 v12.0 (Feature: #96614), while a site set is opt-in
per site. So on a site that does not enable the set
:yaml:`fgtclb/academic-projects` the layout
:typoscript:`pagets__AcademicProject` resolved nowhere: the page properties
showed :guilabel:`[ MISSING LABEL ]` for it and it could not be selected for a
new page at all. On TYPO3 v12 there was no delivery path for the layout
whatsoever, because site sets arrived in TYPO3 v13.1 (Feature: #103437).

:file:`Configuration/page.tsconfig` imports the backend layout now. The
:file:`Configuration/TsConfig/page.tsconfig`, which carried nothing but a
comment, was removed.

Impact
======

The backend layout is available on every installation, whether or not it uses
site sets.

The new content element wizard entries are delivered per component in this
version, through the site sets or the page field :guilabel:`Page TSconfig`, see
:ref:`breaking-site-sets-and-static-templates-restructured`.

Affected Installations
======================

All installations of this extension. Nothing has to be done: pages already
carrying :typoscript:`pagets__AcademicProject` resolve the layout from now on.

..  index:: TSConfig, Backend, ext:academic_projects
