..  _important-study-plan-renders-through-the-default-layout:

============================================================
Important: The study plan renders through the Default layout
============================================================

Description
===========

The study plan content element offers editors the :guilabel:`Appearance` tab
with the layout, the frame, the space before and the space after, and none of
those choices had any effect. The element also carried no :html:`id="c<uid>"` anchor, so a
link pointing at it landed on the page and not on the element, and a
:guilabel:`Section Index` menu built a link to an anchor that did not exist.

The cause was the template. :file:`AcademicStudyPlan.html` rendered its markup
directly, and the :file:`Header/All` partial with it, without a Fluid layout.
The frame wrapper, the spacing classes, the anchor and the footer link are
rendered by the :file:`Layouts/Default.html` layout of whichever package provides
:typoscript:`lib.contentElement` - `EXT:fluid_styled_content`, or a site package
such as :composer:`bk2k/bootstrap-package`, which renders a frame of its own. A
template without that layout gets none of them.

The template now starts with :html:`<f:layout name="Default" />` and wraps its
markup in an :html:`<f:section name="Main">`. The explicit render of
:file:`Header/All` is gone, because the layout renders the header section
itself.

Impact
======

**The rendered markup changes on every installation**, whether or not the
:guilabel:`Appearance` tab was ever touched:

..  code-block:: html
    :caption: Before

    <div class="academic-study-plan container" data-study-plan="1">
        <header><h2>Study plan B.Sc.</h2></header>
        <nav>...</nav>
    </div>

..  code-block:: html
    :caption: After

    <div id="c1" class="frame frame-default frame-type-academic_study_plan frame-layout-0">
        <header><h2>Study plan B.Sc.</h2></header>
        <div class="academic-study-plan container" data-study-plan="1">
            <nav>...</nav>
        </div>
    </div>

Two things moved: the element gained the frame wrapper around it, and the header
moved out of :html:`.academic-study-plan` to above it.

**The settings of the two Appearance palettes start taking effect**, and on an
installation whose editors used that tab some of them were configured long ago:

*   :guilabel:`Layout`, :guilabel:`Frame`, :guilabel:`Space Before` and
    :guilabel:`Space After` - the four fields of the :guilabel:`frames` palette
    - now render their classes, so rulers and spacing appear where they were
    chosen and a layout variant reaches the stylesheet as
    :html:`frame-layout-<n>`.
*   The element carries its :html:`id="c<uid>"`, so a link to it jumps to it.
    That also repairs :guilabel:`Section Index`: the menu element always listed
    the study plan, but linked to an anchor that did not exist.
*   :guilabel:`Link to top` renders the *to top* link of the layout's footer
    section. It is the one addition that shows up with no CSS involved.

The interaction is unchanged. The filter, the semester toggles and the module
dialogs address the markup inside the wrapper, which is untouched, and the
JavaScript contract is the same.

Migration
=========

**A stylesheet that addressed the header inside the study plan wrapper stops
matching it.** The header is now a sibling of that wrapper rather than a child:

..  code-block:: css
    :caption: Before

    .academic-study-plan header h2 { ... }

..  code-block:: css
    :caption: After

    .frame-type-academic_study_plan header h2 { ... }

Dropping the ancestor works as well where the selector is specific enough
without it. Nothing else has to be changed.

Affected Installations
======================

Every installation that renders the study plan content element with the shipped
template, on TYPO3 v12 and v13 alike - the :file:`Layouts/Default.html` of
`EXT:fluid_styled_content` is identical on both. An installation that ships its
own copy of :file:`AcademicStudyPlan.html` keeps rendering that copy and is
unaffected - including a copy that was made to add the layout in the first
place, which can be dropped once nothing else in it was changed.

One case is worth naming because the header does not move there but disappears:
a site package that provides its own :typoscript:`lib.contentElement` whose
:file:`Default` layout renders no :html:`Header` section at all. Such a layout
suppresses the header of every core content element as well, so it is a choice
that site made; the study plan now simply follows it.

.. index:: Frontend, Fluid, ext:academic_study_plan
