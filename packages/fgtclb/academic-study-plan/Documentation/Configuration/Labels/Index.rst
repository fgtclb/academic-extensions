..  index:: Configuration; Labels
..  _configuration-labels:

======
Labels
======

The labels this extension shows in the frontend come from
:file:`EXT:academic_study_plan/Resources/Private/Language/locallang.xlf` and its
translations; the table below names the ones that come from another file. A site
changes a label without copying a template, in TypoScript:
under :typoscript:`plugin.tx_academicstudyplan._LOCAL_LANG`.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicstudyplan._LOCAL_LANG {
      default.credits = ECTS
      de.credits = ECTS
    }

The dots of a key need no escaping: TypoScript reads them as levels of its tree,
and TYPO3 joins the levels to the key again. A label of another language goes
under its language key, :typoscript:`de` for German.

The study plan is a content element, not a plugin: a site sets its labels under
the path of the extension.

A language file override works as well, and replaces the label of the file
itself: :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on
TYPO3 v13, :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on
TYPO3 v14.

Earlier versions of this extension read these overrides on TYPO3 v13 from
:typoscript:`plugin.tx_academic_study_plan` instead, see
:ref:`the changelog <important-label-overrides-use-the-documented-path>`.

Where the labels are shown
==========================

Placeholders in angle brackets stand for a part of the key that the template
or the code fills in, a category type or a field name for example.

..  list-table::
    :header-rows: 1
    :widths: 45 55

    *   - Key
        - Shown by
    *   - :xml:`credits`
        - :file:`Frontend/Default/Partials/StudyPlan/Module.html`, :file:`Frontend/Default/Partials/StudyPlan/ModuleDialog.html`, :file:`Frontend/Default/Partials/StudyPlan/Semester.html`
    *   - :xml:`filter.label`
        - :file:`Frontend/Default/Partials/StudyPlan/Filter.html`, :file:`Frontend/Default/Templates/AcademicStudyPlan.html`
    *   - :xml:`modal.audio`
        - :file:`Frontend/Default/Partials/StudyPlan/ModuleDialog.html`
    *   - :xml:`modal.close`
        - :file:`Frontend/Default/Partials/StudyPlan/ModuleDialog.html`
    *   - :xml:`modal.open`
        - :file:`Frontend/Default/Partials/StudyPlan/Module.html`
