..  _integrator:

===============
For integrators
===============

This extension ships one content element, the study plan. It is a plain
content element, not an Extbase plugin, so it has no FlexForm and no plugin
options. An editor fills it in two fields of the content element itself, the
semesters with their modules and a footer note, and the page of the content
element describes both the way plugin options are described elsewhere.

How the plan behaves for a visitor, whether the page loads its script and
whether the category filter starts folded, is set per site with the site
settings or the TypoScript constants of the site set, see
:ref:`configuration`. Those settings apply to every study plan of the site.

..  list-table::
    :header-rows: 1
    :widths: 10 30 30 30

    *   -   Icon
        -   Content element
        -   CType
        -   Shows
    *   -   ..  figure:: /Images/Icons/study-plan.svg
                :width: 32px
                :alt: Icon of the study plan content element
        -   :ref:`Study Plan <integrator-study-plan>`
        -   ``academic_study_plan``
        -   The semesters of a study programme side by side, with their
            modules, credit points and a filter by category.

..  toctree::
    :maxdepth: 1
    :titlesonly:
    :hidden:

    StudyPlan
