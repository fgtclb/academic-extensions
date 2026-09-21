..  _deprecation-study-plan-class-selectors:

===========================================
Deprecation: The study plan class selectors
===========================================

Description
===========

The script of the study plan content element used to find every part it drives
by class name. It finds them by :html:`data-study-plan-*` attribute now, and
reads the class selector of a part only when the attribute of that part is
nowhere in that plan:

..  list-table::
    :header-rows: 1

    *   -   Attribute
        -   Class selector of 3.0, deprecated
    *   -   :html:`data-study-plan`
        -   :html:`.academic-study-plan`
    *   -   :html:`data-study-plan-filter`
        -   :html:`.filter`
    *   -   :html:`data-study-plan-filter-template`
        -   The first :html:`<li>` inside the filter list
    *   -   :html:`data-study-plan-semester`
        -   :html:`.col`
    *   -   :html:`data-study-plan-semester-header`
        -   :html:`.header`
    *   -   :html:`data-study-plan-module`
        -   :html:`.module`
    *   -   :html:`data-study-plan-dialog-trigger`
        -   :html:`.modal-trigger`
    *   -   :html:`data-study-plan-dialog`
        -   The :html:`<dialog>` inside a module

The fallback is per part, so markup that carries the attributes on some parts
and the classes on others works as well.

The container is the one exception: :html:`.academic-study-plan` is read **next
to** :html:`data-study-plan`, not instead of it. Two plans of one page can come
from different templates, and a 3.0 one must not disappear because the other
carries the attribute. It is deprecated on the same terms.

Impact
======

Nothing is logged and nothing changes for a visitor. The fallback is removed in
version 4.0; from then on markup without the attributes has no interaction at
all - no filter, no accordion, no dialogs.

Affected Installations
======================

Only an installation that overrides the template or a partial of the study plan
with markup of its own. The shipped markup carries the attributes.

Migration
=========

Add the attributes to your markup, on the elements
:ref:`Templates <templates-attributes>` names. The classes may stay: they are
what the stylesheet selects, and nothing reads them once the attributes are
there.

An override that only renamed a few elements is often better replaced by an
override of the one partial it is really about - the other three then keep
coming from the extension, with everything they gain.

.. index:: Frontend, Fluid, JavaScript, ext:academic_study_plan
