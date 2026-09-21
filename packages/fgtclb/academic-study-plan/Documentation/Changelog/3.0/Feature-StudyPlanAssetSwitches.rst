..  _feature-study-plan-assets-can-be-switched-off:

==================================================
Feature: The study plan assets can be switched off
==================================================

Description
===========

The study plan content element registers its stylesheet and its script from
inside its own template, with :html:`<f:asset.css>` and
:html:`<f:asset.module>`. An installation that wanted to style the element
itself therefore had to override the whole template - and then either lost the
script or shipped a copy of it that stopped following the original.

Both assets are switchable now, per site, without touching the template:

..  list-table::
    :header-rows: 1

    *   -   Setting
        -   Default
        -   Off means
    *   -   :yaml:`plugin.tx_academicstudyplan.assets.css`
        -   :yaml:`true`
        -   The page does not load
            :file:`EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css`.
    *   -   :yaml:`plugin.tx_academicstudyplan.assets.js`
        -   :yaml:`true`
        -   The page does not load
            :js:`@fgtclb/academic-study-plan/frontend/academic-study-plan.js`.

They are declared by the site set
:yaml:`fgtclb/academic-study-plan-content-element`, so every site that renders
the element can set them:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin.tx_academicstudyplan.assets.css: false

An installation that configures its frontend through :sql:`sys_template`
records sets the TypoScript constants of the very same names, in
:guilabel:`Constants`:

..  code-block:: typoscript
    :caption: Constants of the root sys_template record

    plugin.tx_academicstudyplan.assets.css = 0

Impact
======

Nothing changes for an installation that configures neither: both assets are
loaded exactly as before, and only a page that carries the element loads them.

**The markup is unchanged either way.** Switching the script off does not
reduce the element to what works without JavaScript - the filter, the semester
accordion and the module dialogs need a script, and an installation that
switches the shipped one off brings its own.

Affected Installations
======================

Every installation that renders the study plan content element. The defaults
keep the assets exactly as they were, so switching nothing on and nothing off
needs no action here.

An installation that overrode :file:`AcademicStudyPlan.html` only to leave out
one of the two assets can drop that override and set the switch instead -
which also puts it back on the shipped script, with the keyboard handling and
the dialog behaviour it has gained since the copy was taken.

Two cases the switches do not reach, both silent:

*   **An installation that keeps its own copy of the template.** The copy is
    rendered as it is, so its own :html:`f:asset` lines keep loading
    unconditionally until they are wrapped in the same :html:`f:if`.
*   **A content object written by hand** rather than copied from
    :file:`setup.typoscript`. It assigns no :typoscript:`settings.assets` block,
    so both conditions are false and the element loads *neither* asset. Copy the
    block, or assign the two keys.

..  note::

    Switching the script off is not the whole story for an installation that
    then brings its own: the filter list item the script clones is rendered
    :html:`hidden`, and a script of your own has to take that attribute off the
    items it builds. See
    :ref:`Important: The study plan filter template item is hidden
    <important-study-plan-filter-template-item-is-hidden>`.

..  note::

    The switches are declared twice, once as a site setting and once as a
    TypoScript constant, with the same default. A site that includes the site
    set **and** the static template reads the constants after the site
    settings, so the constant wins there - one mechanism per site, as
    :ref:`Do not combine both <one-mechanism-per-site>` says.

.. index:: Frontend, Fluid, TypoScript, ext:academic_study_plan
