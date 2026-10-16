..  _integrator:

===============
For integrators
===============

This chapter describes the content elements of :guilabel:`academic_projects`:
what each one renders, where it belongs and what every option in its backend
form does. The site sets, the site settings and the TypoScript the content
elements read are described in :ref:`configuration`.

Both content elements are served by one plugin action and share one set of
options, so their backend forms look the same. They differ in what the field
below the options selects: the pages the projects sit in, or the projects
themselves.

An option an editor stores in a content element wins over a TypoScript value of
the same name below :typoscript:`plugin.tx_academicprojects.settings`, and so
does an empty one: Extbase merges the stored options over the TypoScript
settings with empty values included, and this extension lists no option in
:typoscript:`ignoreFlexFormSettingsIfEmpty`. The one option with a TypoScript
value of the same name is :guilabel:`Show active state badge`, `0` by default,
which therefore reaches only an element that was never saved with that field.
The category filters the form offers are no option of the content elements but
site settings, see :ref:`configuration-list-filter`.

..  list-table::
    :header-rows: 1
    :widths: 10 25 30 35

    *   -   Icon
        -   Content element
        -   CType
        -   Shows
    *   -   ..  figure:: /Images/Icons/projects.svg
                :width: 32px
                :alt: Icon of the Projects content element
        -   :ref:`Projects <integrator-project-list>`
        -   ``academicprojects_projectlist``
        -   The project pages below the chosen pages as cards, with a sorting,
            category filters and a filter by the state of a project.
    *   -   ..  figure:: /Images/Icons/projects.svg
                :width: 32px
                :alt: Icon of the Selected Projects content element
        -   :ref:`Selected Projects <integrator-project-list-single>`
        -   ``academicprojects_projectlistsingle``
        -   The project pages an editor picks one by one, with the same cards
            and the same form.

Both content elements are hidden until a site enables them, see
:ref:`configuration-components`.

..  toctree::
    :hidden:
    :titlesonly:

    ProjectList
    ProjectListSingle
