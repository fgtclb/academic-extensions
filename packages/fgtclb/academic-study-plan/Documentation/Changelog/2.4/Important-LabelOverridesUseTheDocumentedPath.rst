..  _important-label-overrides-use-the-documented-path:

============================================================
Important: Label overrides are read from the documented path
============================================================

Description
===========

The templates of this extension translate their labels with the extension name
:html:`AcademicStudyPlan` instead of the extension key
:html:`academic_study_plan`. TYPO3 v12 and v13 build the TypoScript path of
:typoscript:`_LOCAL_LANG` from that name as it is given, so they read label
overrides from :typoscript:`plugin.tx_academic_study_plan`. They now read them
from :typoscript:`plugin.tx_academicstudyplan`, the path the TYPO3
documentation names and TYPO3 v14 reads anyway.

The study plan is a content element, not a plugin, so only the path of the
extension applies to it.

Every label of the extension, and where it is shown, is listed in
:ref:`configuration-labels`.

Impact
======

On TYPO3 v12 and v13, a label override under
:typoscript:`plugin.tx_academic_study_plan._LOCAL_LANG` no longer has an
effect. Move it to :typoscript:`plugin.tx_academicstudyplan._LOCAL_LANG`:

..  code-block:: typoscript

    plugin.tx_academicstudyplan._LOCAL_LANG.default.credits = ECTS

..  index:: Frontend, Fluid, TypoScript, ext:academic_study_plan
