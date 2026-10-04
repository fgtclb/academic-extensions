..  _breaking-projects-category-icons-follow-the-colour-scheme:

=================================================================
Breaking: Project icons are replaced and follow the colour scheme
=================================================================

Description
===========

The four category type icons of this extension, which
:php:`EXT:category_types` registers as :php:`category_types.projects.*`, were
registered with the core provider
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`. It renders the
default markup - the markup a :php:`sys_category` type icon reaches - as an
:html:`<img>` tag. An image is opaque to CSS, so the icon kept the ink of its
file whatever the backend colour scheme said.

The four category types now ask for inlining with `inlineIcon: true` in
:file:`Configuration/CategoryTypes.yaml`, so :php:`EXT:category_types` registers
them with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines the file in both markups. A category type without that flag
keeps the core provider.

The academic project page type (doktype `30`) and the two content elements
"Projects" and "Projects (selected)" used the core icon
:php:`actions-code-merge`, a merge glyph that says nothing about a project and
that the extension does not ship. They now have icons of their own, registered
in the new :file:`Configuration/Icons.php` of this extension.

Every icon of the extension is a Font Awesome Free solid drawing in
`currentColor`. The category types that mean the same thing as one of
:php:`EXT:academic_base`, the cooperation and the department, draw the shared
file of :php:`EXT:academic_base`.

..  list-table::
    :header-rows: 1

    *   - 2.x identifier
        - 3.0 identifier
        - Registry
    *   - :php:`actions-code-merge` (core), page type `30`: doktype select item
          and :php:`$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][30]`
        - :php:`tx-academicprojects-doktype-project`
        - Backend, :file:`Configuration/Icons.php`
    *   - :php:`actions-code-merge` (core), content elements
          `academicprojects_projectlist` and
          `academicprojects_projectlistsingle`: `CType` select item,
          :php:`typeicon_classes` and new content element wizard
        - :php:`tx-academicprojects-plugin-projects`
        - Backend, :file:`Configuration/Icons.php`
    *   - :php:`category_types.projects.<type>`
        - unchanged
        - Backend and frontend, registered by :php:`EXT:category_types` from
          :file:`Configuration/CategoryTypes.yaml`

:php:`actions-code-merge` stays registered by TYPO3, only this extension does
not use it any more.

The category type identifiers are unchanged, the files they are registered from
moved. The old files are deleted:

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
    *   - `project_department`, `department` before 3.0, see
          :ref:`breaking-projects-project-department-category-type`
        - :file:`CategoryTypes/Department.svg`
        - :file:`EXT:academic_base/Resources/Public/Icons/info/department.svg`
    *   - Group `projects`, :php:`category_types_group.projects`
        - :file:`CategoryGroups/Projects.svg`
        - :file:`EXT:academic_projects/Resources/Public/Icons/category-group/projects.svg`

:file:`Resources/Public/Icons/Extension.svg` stays the icon of the extension.
The licence of the Font Awesome files is listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`, see
:ref:`third-party-icons`.

Impact
======

The four category type icons reach the **frontend**, through the icon ViewHelper
of :guilabel:`academic_base`,
:html:`<ab:icon identifier="category_types.projects.{type}" />`, in
:file:`Partials/Project/Page/Categories.html` and
:file:`Partials/Project/Item.html`. :guilabel:`category_types` registers them
in the frontend icon registry as well, with the same provider, see
:ref:`important-projects-category-icons-come-from-the-frontend-icon-registry`.
Neither call asks for the `inline` markup, so their rendered markup changes: an
:html:`<img>` of a fixed pixel size becomes an inlined :html:`<svg>` with
:html:`width="1em" height="1em"`, which follows the font size and the colour of
the text around it. Every site using the project plugins sees those icons resize,
recolour and change their drawing.

Site CSS or JavaScript that sized, coloured or addressed the :html:`<img>` has
to address the :html:`<svg>` instead.

In the backend, the category type icons take the text colour around them, so
they stay legible in a dark backend colour scheme. The page tree, the page
module, the record list, the page type and content element type selects and the
new content element wizard show the new icons for project pages and project
content elements.

Site CSS or JavaScript that addressed the project page type or the project
content elements through :css:`.icon-actions-code-merge` or
:html:`data-identifier="actions-code-merge"` no longer matches. A site package
that refers to one of the deleted files, from its own
:file:`Configuration/CategoryTypes.yaml`, a template, TypoScript or CSS, points
at nothing.

Affected Installations
======================

Every installation of this extension. Installations that render the project
plugins in the frontend are affected visibly, and so are installations with own
styling for the icons above or with references to the deleted files under
:file:`EXT:academic_projects/Resources/Public/Icons/CategoryTypes/`.

Migration
=========

Replace an image selector with an element selector in the site CSS, for example

..  code-block:: css

    /* before */
    .project-categories .icon img { width: 32px; }

    /* after */
    .project-categories .icon svg { width: 1.25em; }

The icon element keeps the surrounding
:html:`<span class="t3js-icon icon" data-identifier="…">` wrapper, so a
selector written against the wrapper needs no change.

Replace :css:`.icon-actions-code-merge` and
:html:`data-identifier="actions-code-merge"` with the 3.0 identifier from the
first table, and a reference to a deleted file with its replacement from the
second one.

To keep a different icon:

*   For the page type or the content elements, register it under the 3.0
    identifier in the :file:`Configuration/Icons.php` of a site package that
    depends on this extension. They are backend icons, the frontend icon
    registry does not know them.
*   For a category type, in both registries, declare the type again with
    another :yaml:`icon` in the :file:`Configuration/CategoryTypes.yaml` of a
    site package, see the developer documentation of :php:`EXT:category_types`,
    section :guilabel:`Changing only the icon`.
*   For a category type, in the frontend only, register its identifier, for
    example :php:`category_types.projects.competence_field`, in the
    :file:`Configuration/FrontendIcons.php` of a site package.

.. index:: Backend, Frontend, TCA, TSConfig, ext:academic_projects
