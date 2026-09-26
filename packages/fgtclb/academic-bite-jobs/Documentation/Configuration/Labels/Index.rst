..  index:: Configuration; Labels
..  _configuration-labels:

======
Labels
======

The labels this extension shows in the frontend come from
:file:`EXT:academic_bite_jobs/Resources/Private/Language/locallang.xlf` and its
translations; the table below names the ones that come from another file. A site
changes a label without copying a template, in TypoScript:
under :typoscript:`plugin.tx_academicbitejobs._LOCAL_LANG` for every content element
of the extension, or under :typoscript:`plugin.tx_academicbitejobs_<plugin>._LOCAL_LANG`
for one of them. A label set for the plugin wins over one set for the extension.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicbitejobs._LOCAL_LANG {
      default.no-jobs = There are no vacancies right now.
      de.no-jobs = Zurzeit sind keine Stellen ausgeschrieben.
    }

    # Only in one content element:
    plugin.tx_academicbitejobs_list._LOCAL_LANG.default.no-jobs = There are no vacancies right now.

The dots of a key need no escaping: TypoScript reads them as levels of its tree,
and TYPO3 joins the levels to the key again. A label of another language goes
under its language key, :typoscript:`de` for German.

..  list-table:: The path of each content element
    :header-rows: 1

    *   - Content element
        - Path
    *   - :guilabel:`b-ite job list` (:typoscript:`academicbitejobs_list`)
        - :typoscript:`plugin.tx_academicbitejobs_list._LOCAL_LANG`

A language file override works as well, and replaces the label of the file
itself: :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on
TYPO3 v13, :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on
TYPO3 v14.

Earlier versions of this extension read these overrides on TYPO3 v13 from
:typoscript:`plugin.tx_academic_bite_jobs` instead, see
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
    *   - :xml:`jobs.bite.<column>`
        - :file:`Partials/BiteJobs/View/Table.html`
    *   - :xml:`jobs.bite.endsOn`
        - :file:`Partials/BiteJobs/View/Card.html`, :file:`Partials/BiteJobs/View/List.html`
    *   - :xml:`no-jobs`
        - :file:`Templates/BiteJobs/List.html`
