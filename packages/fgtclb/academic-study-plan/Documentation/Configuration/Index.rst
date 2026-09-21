:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This extension ships its frontend TypoScript and its backend page TSconfig in
two forms: as TYPO3 **site sets**, and as classic **static templates** plus
**page TSconfig files** that are selected on a page. Both forms read the very
same files, so they configure an installation identically.

Pick one of them per site and stay with it — see
:ref:`Do not combine both <one-mechanism-per-site>` for what happens otherwise.

..  _configuration-components:

What the sets contain
=====================

The extension ships one content element, so it ships one component set and one
aggregate set that depends on it.

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-study-plan-content-element`
        -   The :guilabel:`Academic Study Plan` content element: its TypoScript
            (:typoscript:`tt_content.academic_study_plan`, the Fluid root paths
            and the data processor that assigns the semesters and modules to the
            template) and the page TSconfig that makes the content element
            selectable in the backend.
    *   -   `fgtclb/academic-study-plan`
        -   Everything above. This is the set to use unless you deliberately
            want a subset.
    *   -   `fgtclb/academic-study-plan-default`
        -   The name this extension published before the sets were cut per
            component. It delivers exactly what `fgtclb/academic-study-plan`
            delivers, and is kept so that existing site configurations keep
            working.

The component set depends on `fgtclb/academic-base-ctype-group`, the set of
:guilabel:`EXT:academic_base` that labels the content element group all academic
extensions sort their elements into.

..  _configuration-hidden-by-default:

The content element is hidden by default
========================================

:guilabel:`EXT:academic_study_plan` hides its content element for the whole
installation and brings it back per component. Whichever of the two mechanisms
below you use, it is what makes :guilabel:`Academic Study Plan` selectable in the
backend again — without one of them the content element is not offered, and
existing records keep rendering.

This is not new in version 2.4: this extension always hid its content element.
What changed is the file that brings it back and the name it is registered
under — see
:ref:`Breaking: Site sets and static templates have been restructured
<breaking-site-sets-and-static-templates-restructured>`.

..  _site-set:

Include the site set
====================

Add the set to the :file:`config.yaml` of the site that should offer the content
element:

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-study-plan

See also `TYPO3 Explained, Using a site set as dependency in a site
<https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`__.

..  _static-templates:

Include static templates
========================

For an installation that still configures its frontend through
:sql:`sys_template` records, the same files are registered as static templates
and as selectable page TSconfig files.

..  tip::

    On TYPO3 v13 and v14 we recommend the site set — and if you use it, do not
    press the backend button :guilabel:`Create a root TypoScript record` on that
    site. The :sql:`sys_template` record it creates carries the flag
    :guilabel:`Clear` for constants and setup, and that flag discards everything
    the site sets contributed. An installation that is already in that state
    gets its configuration back by selecting the static templates below in that
    very record.

..  _static-typoscript:

Include static TypoScript
-------------------------

Edit the :sql:`sys_template` record of the site root and add the entry to
:guilabel:`Include static (from extensions)`:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Study Plan: Content element (academic_study_plan)`
        -   The TypoScript of the :guilabel:`Academic Study Plan` content
            element.
    *   -   :guilabel:`Academic Study Plan: All components (academic_study_plan)`
        -   Every component this extension ships, in one entry.

..  _static-pagetsconfig:

Include static page TSconfig
----------------------------

Edit the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`, and add the entry:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Study Plan: Content element (academic_study_plan)`
        -   Makes the :guilabel:`Academic Study Plan` content element selectable,
            and configures its entry in the new content element wizard.
    *   -   :guilabel:`Academic Study Plan: All components (academic_study_plan)`
        -   Every component this extension ships, in one entry.

The setting is inherited by every page below the one it is set on.

..  _asset-switches:

Skip the stylesheet or the script
=================================

The content element brings two assets to every page that carries it: the
stylesheet :file:`academic-study-plan.css` and the ES module
:js:`@fgtclb/academic-study-plan/frontend/academic-study-plan.js`. A page
without the element loads neither.

Both can be switched off per site — for an installation that styles the element
itself, or that brings its own script:

..  list-table::
    :header-rows: 1

    *   -   Setting
        -   Default
        -   Off means
    *   -   :yaml:`plugin.tx_academicstudyplan.assets.css`
        -   :yaml:`true`
        -   The page does not load the stylesheet.
    *   -   :yaml:`plugin.tx_academicstudyplan.assets.js`
        -   :yaml:`true`
        -   The page does not load the script.

With the site set, they are site settings: edit them in the backend, in the
settings editor of that site, or write them by hand. The editor is the same one
on both TYPO3 versions and only sits elsewhere —
:guilabel:`Site Management > Settings` on TYPO3 v13, and
:guilabel:`Sites > Setup` on TYPO3 v14, which merged it into the module that
edits the site itself.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin.tx_academicstudyplan.assets.css: false

With the static template, they are TypoScript constants of the same names,
:guilabel:`Constants` of the root :sql:`sys_template` record:

..  code-block:: typoscript
    :caption: Constants of the root sys_template record

    plugin.tx_academicstudyplan.assets.css = 0

..  warning::

    Switching the script off leaves the markup exactly as it is. The category
    filter, the semester accordion and the module dialogs do nothing without a
    script — bring your own, addressing the same markup.

    The one part of the markup that is not inert is handled: the single
    :html:`<li>` of the filter list is the template the script clones, and it
    is rendered :html:`hidden` so that its placeholder text never reaches the
    screen. A script of your own has to take that attribute off the items it
    builds from it — otherwise the filter stays empty.

    What stays behind either way is the :html:`<nav>` around that list: a
    labelled navigation landmark with nothing in it until a script fills it.
    It is part of the markup contract, so it is yours to fill or to hide.

    :ref:`Templates <templates>` documents every part the shipped script
    drives, which is the list a script of your own has to address.

..  _collapsible-filter:

Collapse the category filter
============================

The category filter is a row of buttons, one per category the modules of the
plan carry. On a plan with many categories it takes up the first screen before
a visitor has seen a single semester.

Switched on, the filter renders collapsed behind a toggle button that states
whether it is expanded and works by keyboard:

..  list-table::
    :header-rows: 1

    *   -   Setting
        -   Default
        -   On means
    *   -   :yaml:`plugin.tx_academicstudyplan.filter.collapsible`
        -   :yaml:`false`
        -   The filter is hidden until the visitor expands it.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin.tx_academicstudyplan.filter.collapsible: true

..  code-block:: typoscript
    :caption: Constants of the root sys_template record

    plugin.tx_academicstudyplan.filter.collapsible = 1

The toggle is built by the script rather than rendered by the template, for the
same reason the filter buttons are: a plan whose modules carry no category at
all ends up with an empty filter, and a control that expands nothing would be
worse than none. Two consequences follow:

*   Switching :typoscript:`plugin.tx_academicstudyplan.assets.js` off and
    bringing your own script means bringing the toggle as well. The template
    renders :html:`data-study-plan-filter-collapsible` on the filter list and
    nothing else.
*   The label of the toggle is the one the container carries as
    :html:`data-filter-label`, which is the translation of
    :html:`filter.label`. An override that drops that attribute gets no toggle
    at all rather than an unnamed button, and the filter stays expanded.
*   The collapsed state is the :html:`hidden` attribute, and the shipped
    stylesheet is what gives it an effect
    (:css:`.filter[hidden] { display: none }`) - the browser's own rule for it
    loses to any author rule that gives the list a :css:`display`. An
    installation that switches
    :typoscript:`plugin.tx_academicstudyplan.assets.css` off needs that rule in
    its own stylesheet.

..  _one-mechanism-per-site:

Do not combine both
===================

A site that uses the site set **and** the static template reads the shipped
files twice. The site set is applied before the :sql:`sys_template` record, so
the second read happens after the site settings and after
:file:`config/sites/<site>/constants.typoscript` — and it resets every constant
the extension ships a default for back to that default. For this extension those
are the three Fluid root paths of the content element, the two switches of
:ref:`Skip the stylesheet or the script <asset-switches>` and the switch of
:ref:`Collapse the category filter <collapsible-filter>`.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.
