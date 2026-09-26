..  _important-label-overrides-use-the-documented-path:

============================================================
Important: Label overrides are read from the documented path
============================================================

Description
===========

The templates of this extension translate their labels with the extension name
:html:`AcademicProjects` instead of the extension key
:html:`academic_projects`. TYPO3 v12 and v13 build the TypoScript path of
:typoscript:`_LOCAL_LANG` from that name as it is given, so they read label
overrides from :typoscript:`plugin.tx_academic_projects`. They now read them
from :typoscript:`plugin.tx_academicprojects` and
:typoscript:`plugin.tx_academicprojects_<plugin>`, the paths the TYPO3
documentation names and TYPO3 v14 reads anyway.

The options of the sorting select, which its view helper translates, follow the
same rule.

An override under :typoscript:`plugin.tx_academicprojects` could reach some
labels on TYPO3 v12 and v13 already, depending on what the page had rendered
before them. It now reaches every label.

Every label of the extension, and where it is shown, is listed in
:ref:`configuration-labels`.

Impact
======

On TYPO3 v12 and v13, a label override under
:typoscript:`plugin.tx_academic_projects._LOCAL_LANG` no longer has an effect.
Move it to :typoscript:`plugin.tx_academicprojects._LOCAL_LANG`, or to the path
of the one plugin it is meant for:

..  code-block:: typoscript

    plugin.tx_academicprojects._LOCAL_LANG.default.sorting.field.label = Sort by
    plugin.tx_academicprojects_projectlist._LOCAL_LANG.default.sorting.field.label = Sort by

The labels of the filter form moved to this path with
:ref:`important-1790383006`.

..  index:: Frontend, Fluid, TypoScript, ext:academic_projects
