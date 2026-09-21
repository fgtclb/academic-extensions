..  _important-study-plan-filter-template-item-is-hidden:

========================================================
Important: The study plan filter template item is hidden
========================================================

Description
===========

The category filter of the study plan content element is built by its script,
not by Fluid. The template renders a single :html:`<li>` holding a button with
the literal placeholders :html:`category-id-placeholder`,
:html:`category-color-placeholder` and :html:`category-label-placeholder`; the
script empties the list, clones that item once per category a module actually
carries, and substitutes the three values.

Nothing hid that item in the meantime. On a page where the script did not run -
because it failed to load, because it was blocked, or because the installation
does not ship it - the filter rendered as a single button reading
:html:`category-label-placeholder`.

The item is rendered :html:`hidden` now, and the script removes the attribute
from each clone it appends.

Impact
======

**The rendered markup changes on every installation**, whether or not the
script runs:

..  code-block:: html
    :caption: Before

    <ul class="filter">
        <li><button data-category-id="category-id-placeholder">category-label-placeholder</button></li>
    </ul>

..  code-block:: html
    :caption: After

    <ul class="filter">
        <li hidden><button data-category-id="category-id-placeholder">category-label-placeholder</button></li>
    </ul>

Where the shipped script runs, the rendered filter is unchanged: the clones it
appends carry no :html:`hidden` attribute, and the template item is gone before
the page is interactive. The attribute needs no stylesheet to take effect - it
is the user agent rule that hides a :html:`hidden` element.

Migration
=========

**An installation that drives this markup with a script of its own has to take
the attribute off the items it builds**, as the shipped one does:

..  code-block:: javascript

    item.removeAttribute('hidden');

Without that, such an installation renders no filter buttons at all after the
update. An installation using the shipped script has nothing to do.

Affected Installations
======================

Every installation that renders the study plan content element with the shipped
template. An installation that ships its own copy of
:file:`AcademicStudyPlan.html` keeps rendering that copy, and keeps the visible
placeholder with it, until the :html:`hidden` attribute is added there too.

.. index:: Frontend, Fluid, JavaScript, ext:academic_study_plan
