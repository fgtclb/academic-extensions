..  _important-1790383006:

=======================================================================
Important: Label overrides of the filters are read from the plugin path
=======================================================================

Description
===========

The filter form of the :guilabel:`Projects` and the :guilabel:`Projects
(selected)` content elements translates its labels with the extension name
:html:`AcademicProjects` instead of :html:`academic_projects`. TYPO3 v12 and v13
build the TypoScript path of :typoscript:`_LOCAL_LANG` from that name as it is
given, so they read label overrides of the filters from
:typoscript:`plugin.tx_academic_projects`. They now read them from
:typoscript:`plugin.tx_academicprojects` and
:typoscript:`plugin.tx_academicprojects_<plugin>`, the paths the TYPO3
documentation names and TYPO3 v14 reads anyway.

It concerns the labels the filter form renders:
:xml:`sys_category.projects.<type>`, :xml:`sys_category.projects.allOptions`,
:xml:`sys_category.projects.allOptions.<type>` and :xml:`filter.moreFilters`.

Impact
======

On TYPO3 v12 and v13, an override of one of these labels under
:typoscript:`plugin.tx_academic_projects._LOCAL_LANG` no longer reaches the
filter form. Move it to
:typoscript:`plugin.tx_academicprojects._LOCAL_LANG`:

..  code-block:: typoscript

    plugin.tx_academicprojects._LOCAL_LANG.default.sys_category.projects.allOptions = All
    plugin.tx_academicprojects._LOCAL_LANG.de.sys_category.projects.allOptions = Alle

The other templates of the extension read their label overrides from the same
path since :ref:`important-label-overrides-use-the-documented-path`. An override of
a type label, which they render too, therefore moves as a whole.

..  index:: Frontend, Fluid, TypoScript, ext:academic_projects
