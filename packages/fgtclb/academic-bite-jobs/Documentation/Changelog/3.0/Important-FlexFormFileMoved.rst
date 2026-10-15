..  _important-bite-jobs-flexform-file-moved:

================================================================
Important: The FlexForm file moved to `Configuration/FlexForms/`
================================================================

Description
===========

Version 2.4 shipped the data structure of the plugin once per TYPO3 version,
as :file:`Configuration/FlexForms/Core12/AcademicBiteJobsList.xml` and
:file:`Configuration/FlexForms/Core13/AcademicBiteJobsList.xml`. Version 3.0
ships it once, for TYPO3 v13 and v14 alike, as
:file:`Configuration/FlexForms/AcademicBiteJobsList.xml`. The extension
registers the new file itself.

Impact
======

TCA of a project that names one of the old files as the data structure of a
content type, for example
``FILE:EXT:academic_bite_jobs/Configuration/FlexForms/Core13/AcademicBiteJobsList.xml``,
points at a file that no longer exists. TYPO3 throws an exception with the
code 1478105826 as soon as it resolves that data structure, for example when
an editor opens the content element.

Affected Installations
======================

Installations whose own TCA names the FlexForm file of this extension by its
path. A project that registers a copy of its own is not affected.

Migration
=========

Replace the path with
``FILE:EXT:academic_bite_jobs/Configuration/FlexForms/AcademicBiteJobsList.xml``.
The same file serves TYPO3 v13 and v14.

..  index:: FlexForm, TCA, NotScanned, ext:academic_bite_jobs
