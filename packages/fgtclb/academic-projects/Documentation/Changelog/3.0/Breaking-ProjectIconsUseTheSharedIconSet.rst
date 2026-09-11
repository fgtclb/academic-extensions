..  _breaking-projects-icons-use-the-shared-icon-set:

===============================================
Breaking: Project icons use the shared icon set
===============================================

Description
===========

The academic project page type (doktype `30`) and the two content elements
"Projects" and "Projects (selected)" used the core icon
:php:`actions-code-merge`, a merge glyph that says nothing about a project and
that the extension did not ship. The four category types drew their icons
from copies of core glyphs under :file:`Resources/Public/Icons/CategoryTypes/`.

All of them are replaced by Font Awesome Free solid icons, drawn in
`currentColor` and inlined by
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`, so
they follow the text colour in both backend colour schemes and in the frontend
(ACE-592). The extension now registers icons of its own in the new
:file:`Configuration/Icons.php`. Icons that mean the same thing in several
academic extensions come from the shared set of :php:`EXT:academic_base`.

The page type and the content elements get identifiers of their own; the old
one is a core identifier and stays registered by core:

..  list-table::
    :header-rows: 1

    *   - Used for
        - Before
        - After
    *   - Page type `30`: doktype select item and
          :php:`$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][30]`
        - :php:`actions-code-merge`
        - :php:`tx-academicprojects-doktype-project`
    *   - Content elements `academicprojects_projectlist` and
          `academicprojects_projectlistsingle`: `CType` select item,
          :php:`typeicon_classes` and new content element wizard
        - :php:`actions-code-merge`
        - :php:`tx-academicprojects-plugin-projects`

The identifiers of the category type icons, :php:`category_types.projects.*`,
are unchanged. Only the files they are registered from moved, and the old
files are deleted:

..  list-table::
    :header-rows: 1

    *   - Category type
        - Before (`EXT:academic_projects/Resources/Public/Icons/`)
        - After
    *   - `competence_field`
        - :file:`CategoryTypes/CompetenceField.svg`
        - :file:`EXT:academic_projects/Resources/Public/Icons/category-type/competence-field.svg`
    *   - `cooperation`
        - :file:`CategoryTypes/Cooperation.svg`
        - :file:`EXT:academic_base/Resources/Public/Icons/info/partnership.svg`
    *   - `funding_partner`
        - :file:`CategoryTypes/FundingPartner.svg`
        - :file:`EXT:academic_projects/Resources/Public/Icons/category-type/funding-partner.svg`
    *   - `department`
        - :file:`CategoryTypes/Department.svg`
        - :file:`EXT:academic_base/Resources/Public/Icons/info/department.svg`

The `groups` entry of :file:`Configuration/CategoryTypes.yaml` named
:file:`CategoryGroups/Projects.svg`, a file that never existed. It now names
:file:`category-group/projects.svg`, which does. Category group icons are not
read by :php:`EXT:category_types` yet, so nothing renders it today.

:file:`Resources/Public/Icons/Extension.svg` stays the icon of the extension in
the extension manager and is not used for anything else. The licence of the
Font Awesome files is listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.

Impact
======

The page tree, the page module, the record list, the content element type
selector and the new content element wizard show the new icons for project
pages and project content elements. The category type icons change their
drawing in the backend and in the frontend.

Site CSS or JavaScript that addressed the project page type or the project
content elements through :css:`.icon-actions-code-merge` or
:html:`data-identifier="actions-code-merge"` no longer matches.

A site package that refers to one of the deleted files - from its own
:file:`Configuration/CategoryTypes.yaml`, a template, TypoScript or CSS - points
at nothing.

Affected Installations
======================

Every installation of this extension. Visibly affected are installations with
own styling for the icons above, and installations that refer to the deleted
files under :file:`EXT:academic_projects/Resources/Public/Icons/CategoryTypes/`.

Migration
=========

Replace :css:`.icon-actions-code-merge` and
:html:`data-identifier="actions-code-merge"` with the new identifier from the
first table, and a reference to a deleted file with its replacement from the
second one.

To keep a different icon, register it under the new identifier in the
:file:`Configuration/Icons.php` of a site package loaded after this extension.
An icon registered under an identifier that is already registered replaces the
earlier registration.

.. index:: Backend, Frontend, TCA, TSConfig, ext:academic_projects
