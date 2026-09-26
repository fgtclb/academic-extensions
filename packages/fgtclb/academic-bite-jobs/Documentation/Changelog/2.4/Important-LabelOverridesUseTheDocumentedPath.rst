..  _important-label-overrides-use-the-documented-path:

============================================================
Important: Label overrides are read from the documented path
============================================================

Description
===========

The templates of this extension translate their labels with the extension name
:html:`AcademicBiteJobs` instead of the extension key
:html:`academic_bite_jobs`. TYPO3 v12 and v13 build the TypoScript path of
:typoscript:`_LOCAL_LANG` from that name as it is given, so they read label
overrides from :typoscript:`plugin.tx_academic_bite_jobs`. They now read them
from :typoscript:`plugin.tx_academicbitejobs` and
:typoscript:`plugin.tx_academicbitejobs_<plugin>`, the paths the TYPO3
documentation names and TYPO3 v14 reads anyway.

Every label of the extension, and where it is shown, is listed in
:ref:`configuration-labels`.

Impact
======

On TYPO3 v12 and v13, a label override under
:typoscript:`plugin.tx_academic_bite_jobs._LOCAL_LANG` no longer has an effect.
Move it to :typoscript:`plugin.tx_academicbitejobs._LOCAL_LANG`, or to the path
of the one plugin it is meant for:

..  code-block:: typoscript

    plugin.tx_academicbitejobs._LOCAL_LANG.default.no-jobs = There are no vacancies right now.
    plugin.tx_academicbitejobs_list._LOCAL_LANG.default.no-jobs = There are no vacancies right now.

..  index:: Frontend, Fluid, TypoScript, ext:academic_bite_jobs
