..  _breaking-study-plan-control-icons-moved-to-the-frontend-icon-registry:

==========================================================================
Breaking: The study plan control icons moved to the frontend icon registry
==========================================================================

Description
===========

The three control icons of the study plan are shown in the frontend only: the
plus and the minus glyph of a semester header, ``academic-study-plan-plus`` and
``academic-study-plan-minus``, and the close glyph of a module dialog,
``academic-study-plan-close``. They were registered in
:file:`Configuration/Icons.php`, for the icon registry of the TYPO3 backend,
and the partials rendered them with ``core:icon``.

They are now registered in :file:`Configuration/FrontendIcons.php`, for the
frontend icon registry of :guilabel:`academic_base`, and are no longer
registered in :file:`Configuration/Icons.php`. The partials
:file:`Frontend/Default/Partials/StudyPlan/Semester.html` and
:file:`Frontend/Default/Partials/StudyPlan/ModuleDialog.html` render them with
the ``ab:icon`` ViewHelper of :guilabel:`academic_base`, with the arguments
they had. Identifiers, files and icon provider are unchanged.

The icon of the content element, ``academic-study-plan``, and the record icons
of categories, semesters and modules stay in :file:`Configuration/Icons.php`.

Impact
======

Two things change for a site, and neither shows an error:

*   A site package that replaced one of the three glyphs in its own
    :file:`Configuration/Icons.php` sees the shipped glyph again in the study
    plan. The frontend icon registry does not read that file.
*   An override of :file:`Semester.html` or :file:`ModuleDialog.html` that
    still renders one of the three glyphs with ``core:icon`` shows TYPO3's
    not-found icon in its place, because the icon registry of the backend no
    longer knows the identifier.

The rendered markup of the glyphs is the same as before, with the same
classes, attributes and inlined SVG. The shipped stylesheet switches the plus
and the minus glyph of a semester header through the classes
:css:`icon-academic-study-plan-plus` and :css:`icon-academic-study-plan-minus`
and hides :css:`.icon` in the header on a wide viewport, and keeps doing so.

Affected Installations
======================

Every installation whose site package replaces one of the three glyphs, or
overrides one of the two partials.

Migration
=========

#.  Move a replacement of one of the three glyphs from the
    :file:`Configuration/Icons.php` of the site package to its
    :file:`Configuration/FrontendIcons.php`. The file has the format of
    :file:`Icons.php`. The site package has to depend on
    :guilabel:`academic_study_plan`, so its entry is read after the shipped
    one:

    ..  code-block:: php
        :caption: EXT:mysitepackage/Configuration/FrontendIcons.php

        <?php

        use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

        return [
            'academic-study-plan-close' => [
                'provider' => SvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/close.svg',
            ],
        ];

#.  In an override of :file:`Semester.html` or :file:`ModuleDialog.html`,
    replace ``<core:icon`` with ``<ab:icon`` for the three identifiers, keep
    every argument, and declare the namespace in the :html:`<html>` tag of the
    partial:
    ``xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"``. An
    override of :file:`Semester.html` keeps rendering
    ``academic-study-plan-plus`` and ``academic-study-plan-minus``, because the
    shipped stylesheet selects their classes, see
    :ref:`The glyphs of a semester header <templates-glyphs>`.
#.  Flush the TYPO3 caches, so the icon registries and the Fluid template cache
    are rebuilt.

..  index:: Fluid, Frontend, ext:academic_study_plan
