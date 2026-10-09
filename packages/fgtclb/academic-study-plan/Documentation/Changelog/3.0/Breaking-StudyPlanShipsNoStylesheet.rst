..  _breaking-study-plan-ships-no-stylesheet:

============================================
Breaking: The study plan ships no stylesheet
============================================

Description
===========

The study plan content element no longer brings a stylesheet. The file
:file:`EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css`
and its source are removed, and the template of the element registers no
stylesheet any more. The markup carries the classes and data attributes
:ref:`Templates <templates>` documents, and the site package styles it.

The setting that switched the stylesheet off is removed with it. The
development versions of 3.0 introduced it, no release of version 2 has it:

*   the site setting :yaml:`plugin.tx_academicstudyplan.assets.css` of the set
    :yaml:`fgtclb/academic-study-plan-content-element`,
*   the TypoScript constant of the same name, and
*   the key :typoscript:`settings.assets.css` of the content object
    :typoscript:`tt_content.academic_study_plan`.

The switch of the script, :yaml:`plugin.tx_academicstudyplan.assets.js`, stays.

The rules the extension shipped are kept as an example in the mono repository
the extension is developed in: the stylesheet of its development instances,
`_academic-study-plan.scss of EXT:academics_dev_site
<https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-study-plan.scss>`__,
which is never shipped with an extension.

Impact
======

A page with a study plan renders the element without any styling of its own.
Beyond its appearance, three parts of the element depend on rules of a
stylesheet and do not work without them:

*   A semester header shows the expand and the collapse glyph at the same
    time. Which one is hidden depends on the classes the script writes.
*   The collapsible filter stays on screen while it is collapsed whenever a
    rule of the site gives the filter list a :css:`display`, because the
    browser's own rule for the :html:`hidden` attribute loses to it.
*   The accordion of a narrow viewport and the highlighting of a category have
    no visible effect: the script only writes the classes :html:`open` and
    :html:`highlighted`.

The shipped icons carry a size of their own, `1em`, and stay visible. A
replacement drawing a site package registers without a width and a height of
its own does not: it collapses to nothing unless a rule of the site sizes the
icon, as the example below does with `1.25rem`.

A site setting :yaml:`plugin.tx_academicstudyplan.assets.css` that is still
present is ignored, and so is the TypoScript constant.

Affected Installations
======================

Every installation that renders the study plan content element and relied on
the stylesheet of the extension, and every installation that set
:yaml:`plugin.tx_academicstudyplan.assets.css`.

Changelog entries of 3.0 that speak of the stylesheet of the element describe
the stylesheet this entry removes. Its rules are the ones of the example above.

Migration
=========

#.  Style the element in the site package. Copy the rules of the example above
    into the stylesheet of the site, or the rules of the stylesheet the
    installation loaded so far, and adapt them to the theme. Keep at least the
    three parts listed under *Impact*, and the size of the icons when the site
    replaces one.
#.  Remove :yaml:`plugin.tx_academicstudyplan.assets.css` from the site
    settings, or the constant from the :guilabel:`Constants` of the
    :sql:`sys_template` record.
#.  An overridden :file:`AcademicStudyPlan.html` that registers the removed
    file with :html:`<f:asset.css>` drops that line, the file does not exist
    any more.

.. index:: Frontend, Fluid, TypoScript, ext:academic_study_plan
