..  _breaking-study-plan-icons-replaced-by-font-awesome:

======================================================
Breaking: Icons replaced and their identifiers renamed
======================================================

Description
===========

The academic extensions now draw every icon from one set, Font Awesome Free
(solid), registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
and drawn in `currentColor`, so each icon takes the colour of the surrounding
text - in both backend colour schemes and in the frontend (ACE-593).

In this extension that fixes two defects. The content element icon
:php:`academic-study-plan` was a fixed-colour drawing on the core provider.
And the three frontend controls - the plus and minus of the semester accordion
on narrow screens and the close button of the module dialog - were rendered
**0 px wide**, so none of them was visible: their files carried a
:html:`viewBox` but no :html:`width` and :html:`height`, and an inline SVG
without a size collapses inside the flex header and inside the shrink-to-fit
close button. The new files carry :html:`width="1em" height="1em"`, and the
stylesheet sizes the icons of the element to `1em` on top of that, so a
project that registers a drawing without a size of its own does not bring the
defect back.

The frontend controls are no longer registered by this extension. They use the
shared action icons of `EXT:academic_base`.

The identifiers follow the scheme
`tx-<extension key without underscores>-<group>-<name>` of all academic
extensions. The previous identifiers are removed without an alias:

..  list-table::
    :header-rows: 1

    *   -   Previous identifier
        -   New identifier
        -   Used for
    *   -   :php:`academic-study-plan`
        -   :php:`tx-academicstudyplan-plugin-study-plan`
        -   Content element "Study plan" (page module and new content element
            wizard)
    *   -   :php:`academic-study-plan-category`
        -   :php:`tx-academicstudyplan-record-category`
        -   Record type :sql:`tx_academicstudyplan_domain_model_category`
    *   -   :php:`academic-study-plan-semester`
        -   :php:`tx-academicstudyplan-record-semester`
        -   Record type :sql:`tx_academicstudyplan_domain_model_semester`
    *   -   :php:`academic-study-plan-module`
        -   :php:`tx-academicstudyplan-record-module`
        -   Record type :sql:`tx_academicstudyplan_domain_model_module`
    *   -   :php:`academic-study-plan-plus`
        -   :php:`tx-academicbase-action-expand`
        -   Opens a semester of the accordion (frontend)
    *   -   :php:`academic-study-plan-minus`
        -   :php:`tx-academicbase-action-collapse`
        -   Closes a semester of the accordion (frontend)
    *   -   :php:`academic-study-plan-close`
        -   :php:`tx-academicbase-action-close`
        -   Closes the module dialog (frontend)

The files :file:`category.svg`, :file:`semester.svg`, :file:`module.svg`,
:file:`plus.svg`, :file:`minus.svg` and :file:`close.svg` in
:file:`Resources/Public/Icons/` are removed. The new files live in
:file:`Resources/Public/Icons/plugin/` and :file:`Resources/Public/Icons/record/`.
:file:`Resources/Public/Icons/Extension.svg` stays, as the extension icon
only. The origin and licence of the Font Awesome files (CC BY 4.0) are listed
in :file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.

Impact
======

An icon requested with one of the previous identifiers - in a template, in
page TSconfig, in TCA or through :php:`IconFactory::getIcon()` - renders the
core placeholder `default-not-found`. A :file:`Configuration/Icons.php` of a
project that overrides one of the previous identifiers no longer has any
effect.

The shipped stylesheet toggles the accordion icons through the classes the
icon markup carries. It now selects `.icon-tx-academicbase-action-expand` and
`.icon-tx-academicbase-action-collapse`; a project stylesheet or an overridden
template that still uses `.icon-academic-study-plan-plus` or
`.icon-academic-study-plan-minus` shows both icons, or neither.

Affected Installations
======================

Installations that override the template
:file:`Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html`,
reference or override one of the previous identifiers, or style the generated
`.icon-*` classes. Installations that use the extension as shipped need no
change.

Migration
=========

Replace the previous identifiers with the new ones from the table above, in
overridden templates, page TSconfig, TCA overrides and
:file:`Configuration/Icons.php`, and `.icon-<previous identifier>` selectors
with `.icon-<new identifier>`. To show a different drawing, register the new
identifier again in the :file:`Configuration/Icons.php` of the project -
for the three frontend controls that changes them in every academic extension
that uses them.

..  index:: Backend, Frontend, TCA, TSConfig, ext:academic_study_plan
