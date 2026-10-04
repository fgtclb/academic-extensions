..  _breaking-study-plan-control-icons-moved-to-the-frontend-icon-registry:

==============================================================================
Breaking: The study plan icons are renamed and the controls are frontend icons
==============================================================================

Description
===========

In 2.x the study plan registered seven icons in :file:`Configuration/Icons.php`,
for the icon registry of the TYPO3 backend: the icon of the content element,
the record icons of categories, semesters and modules, and the three control
icons of the frontend, the plus and the minus glyph of a semester header and
the close glyph of a module dialog. The partials rendered the controls with
``core:icon``.

The academic extensions now draw every icon from one set, Font Awesome Free
(solid), drawn in :css:`currentColor` so each icon takes the colour of the
surrounding text, in both backend colour schemes and in the frontend
(ACE-593). The identifiers follow the scheme
``tx-<extension key without underscores>-<group>-<name>``, and the group
decides the registry:

*   The content element icon and the three record icons are shown by the
    backend only. They stay in :file:`Configuration/Icons.php` under new
    identifiers. The content element icon was a placeholder drawing in fixed
    colours, and now follows the colour scheme like the record icons.
*   The three controls are shown by the frontend only. They are no longer
    registered by this extension: the partials
    :file:`Frontend/Default/Partials/StudyPlan/Semester.html` and
    :file:`Frontend/Default/Partials/StudyPlan/ModuleDialog.html` render the
    shared action icons of :guilabel:`academic_base` from its frontend icon
    registry, with the ``ab:icon`` ViewHelper of :guilabel:`academic_base` and
    the arguments they had (ACE-813). The extension ships no
    :file:`Configuration/FrontendIcons.php` any more.

The previous identifiers are removed without an alias:

..  list-table::
    :header-rows: 1

    *   -   2.x identifier
        -   3.0 identifier
        -   Registry
    *   -   ``academic-study-plan``
        -   ``tx-academicstudyplan-plugin-study-plan``
        -   :file:`Configuration/Icons.php`
    *   -   ``academic-study-plan-category``
        -   ``tx-academicstudyplan-record-category``
        -   :file:`Configuration/Icons.php`
    *   -   ``academic-study-plan-semester``
        -   ``tx-academicstudyplan-record-semester``
        -   :file:`Configuration/Icons.php`
    *   -   ``academic-study-plan-module``
        -   ``tx-academicstudyplan-record-module``
        -   :file:`Configuration/Icons.php`
    *   -   ``academic-study-plan-plus``
        -   ``tx-academicbase-action-expand``
        -   :file:`Configuration/FrontendIcons.php` of :guilabel:`academic_base`
    *   -   ``academic-study-plan-minus``
        -   ``tx-academicbase-action-collapse``
        -   :file:`Configuration/FrontendIcons.php` of :guilabel:`academic_base`
    *   -   ``academic-study-plan-close``
        -   ``tx-academicbase-action-close``
        -   :file:`Configuration/FrontendIcons.php` of :guilabel:`academic_base`

The files :file:`content-element.svg`, :file:`category.svg`,
:file:`semester.svg`, :file:`module.svg`, :file:`plus.svg`, :file:`minus.svg`
and :file:`close.svg` in :file:`Resources/Public/Icons/` are removed. The new
files live in :file:`Resources/Public/Icons/plugin/` and
:file:`Resources/Public/Icons/record/`. :file:`Resources/Public/Icons/Extension.svg`
stays, as the extension icon only. The origin and licence of the Font Awesome
files (CC BY 4.0) are listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.

The controls are still inlined SVG. Their files used to carry a
:html:`viewBox` but no :html:`width` and :html:`height`, and an inline SVG
without a size collapses to 0 pixels inside the flex header of a semester and
inside the close button of a dialog, so none of the three was visible. The new
files carry :html:`width="1em" height="1em"`, and the shipped stylesheet sizes
every icon of the element on top of that, see
:ref:`important-study-plan-controls-are-visible`.

Impact
======

*   An icon requested with one of the previous identifiers renders a
    not-found placeholder: in TCA, page TSconfig and through
    :php:`IconFactory::getIcon()` the one of TYPO3, with ``ab:icon`` the one of
    :guilabel:`academic_base`. A site package that registers one of the
    previous identifiers in its :file:`Configuration/Icons.php` or
    :file:`Configuration/FrontendIcons.php` no longer changes anything.
*   An override of :file:`Semester.html` or :file:`ModuleDialog.html` that
    renders a control with ``core:icon`` shows TYPO3's not-found icon, because
    the icon registry of the backend does not know the identifiers of the
    frontend icon registry.
*   The class the icon markup derives from the identifier changes with it. The
    shipped stylesheet switches the glyphs of a semester header through
    :css:`icon-tx-academicbase-action-expand` and
    :css:`icon-tx-academicbase-action-collapse` now, and still hides
    :css:`.icon` in the header on a wide viewport. A stylesheet or an override
    that still uses :css:`icon-academic-study-plan-plus` or
    :css:`icon-academic-study-plan-minus` shows both glyphs, or neither.
*   A replacement of a control is no longer limited to the study plan. Its
    identifier belongs to :guilabel:`academic_base`, so a site package that
    replaces it changes the glyph in every academic extension that renders it.

Affected Installations
======================

Every installation whose site package replaces one of the icons, names one of
the previous identifiers in TCA or page TSconfig, overrides one of the two
partials, or styles the generated :css:`icon-*` classes. Installations that use
the extension as shipped need no change.

Migration
=========

#.  Replace the previous identifiers with the 3.0 identifiers from the table
    above, in TCA overrides, page TSconfig and overridden templates.
#.  To draw the content element or a record icon differently, register its
    3.0 identifier in the :file:`Configuration/Icons.php` of the site package.
#.  To draw a control differently, register its 3.0 identifier in the
    :file:`Configuration/FrontendIcons.php` of the site package, not in its
    :file:`Configuration/Icons.php`. The file has the format of
    :file:`Icons.php`. The site package has to depend on
    :guilabel:`academic_base`, which registers the control icons, so its entry
    is read after the shipped one:

    ..  code-block:: php
        :caption: EXT:mysitepackage/Configuration/FrontendIcons.php

        <?php

        use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

        return [
            'tx-academicbase-action-close' => [
                'provider' => SvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/close.svg',
            ],
        ];

#.  In an override of :file:`Semester.html` or :file:`ModuleDialog.html`,
    render the controls with ``<ab:icon`` instead of ``<core:icon``, keep every
    argument, and declare the namespace in the :html:`<html>` tag of the
    partial:
    ``xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"``. An
    override of :file:`Semester.html` keeps rendering
    ``tx-academicbase-action-expand`` and ``tx-academicbase-action-collapse``,
    because the shipped stylesheet selects their classes, see
    :ref:`The glyphs of a semester header <templates-glyphs>`.
#.  Replace :css:`.icon-<2.x identifier>` selectors with
    :css:`.icon-<3.0 identifier>`.
#.  Flush the TYPO3 caches, so the icon registries and the Fluid template cache
    are rebuilt.

..  index:: Backend, Fluid, Frontend, TCA, TSConfig, ext:academic_study_plan
