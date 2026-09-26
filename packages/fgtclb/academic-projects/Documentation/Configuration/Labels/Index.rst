..  index:: Configuration; Labels
..  _configuration-labels:

======
Labels
======

The labels this extension shows in the frontend come from
:file:`EXT:academic_projects/Resources/Private/Language/locallang.xlf` and its
translations; the table below names the ones that come from another file. A site
changes a label without copying a template, in TypoScript:
under :typoscript:`plugin.tx_academicprojects._LOCAL_LANG` for every content element
of the extension, or under :typoscript:`plugin.tx_academicprojects_<plugin>._LOCAL_LANG`
for one of them. A label set for the plugin wins over one set for the extension.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicprojects._LOCAL_LANG {
      default.project.runtime = Duration
      de.project.runtime = Laufzeit
    }

    # Only in one content element:
    plugin.tx_academicprojects_projectlist._LOCAL_LANG.default.project.runtime = Duration

The dots of a key need no escaping: TypoScript reads them as levels of its tree,
and TYPO3 joins the levels to the key again. A label of another language goes
under its language key, :typoscript:`de` for German.

..  list-table:: The path of each content element
    :header-rows: 1

    *   - Content element
        - Path
    *   - :guilabel:`Projects` (:typoscript:`academicprojects_projectlist`)
        - :typoscript:`plugin.tx_academicprojects_projectlist._LOCAL_LANG`
    *   - :guilabel:`Projects (selected)` (:typoscript:`academicprojects_projectlistsingle`)
        - :typoscript:`plugin.tx_academicprojects_projectlistsingle._LOCAL_LANG`

The page template of a project page is not rendered by a plugin: a site sets
its labels under the path of the extension.

A language file override works as well, and replaces the label of the file
itself: :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on
TYPO3 v13, :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on
TYPO3 v14.

Earlier versions of this extension read these overrides on TYPO3 v13 from
:typoscript:`plugin.tx_academic_projects` instead, see
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
    *   - :xml:`activeState.<state>`
        - :file:`Partials/Project/DemandActiveState.html`
    *   - :xml:`activeState.label`
        - :file:`Partials/Project/DemandActiveState.html`
    *   - :xml:`filter.moreFilters`
        - :file:`Partials/Project/DemandCategories.html`
    *   - :xml:`list.noProjectsFound`
        - :file:`Partials/Project/ItemList.html`
    *   - :xml:`project.budget`
        - :file:`Pages/AcademicProject.html`
    *   - :xml:`project.funders`
        - :file:`Pages/AcademicProject.html`
    *   - :xml:`project.runtime`
        - :file:`Pages/AcademicProject.html`
    *   - :xml:`project.since`
        - :file:`Pages/AcademicProject.html`
    *   - :xml:`project.until`
        - :file:`Pages/AcademicProject.html`
    *   - :xml:`sorting.direction.label`
        - :file:`Partials/Project/DemandSorting.html`
    *   - :xml:`sorting.field.label`
        - :file:`Partials/Project/DemandSorting.html`
    *   - :xml:`sys_category.projects.<type>`
        - :file:`Pages/AcademicProject.html`, :file:`Partials/Project/DemandCategories.html`, :file:`Partials/Project/Item.html`
    *   - :xml:`sys_category.projects.allOptions`
        - :file:`Partials/Project/DemandCategories.html`
    *   - :xml:`sys_category.projects.allOptions.<type>`
        - :file:`Partials/Project/DemandCategories.html`
    *   - :xml:`sorting.field.<field>`, :xml:`sorting.direction.<direction>`
        - The options of the sorting select, translated by its view helper
