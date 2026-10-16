..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 delivers the configuration of :guilabel:`academic_projects` per
component, hides its content elements until a site enables them and resolves
the links in the rich text of a project. Its FlexForm upgrade wizard migrates
the plugin settings of older project lists for the first time, so it has to run
again where it ran before. Work through the sections in order. Skip a step that
does not apply to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_projects` 2.4 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which
    :guilabel:`academic_base` 2.4 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 2.4.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.
*   :composer:`fgtclb/category-types` 2.4, the same release as this extension.

On TYPO3 12, make sure every site that renders projects defines
:typoscript:`lib.parseFunc_RTE`, before the update. The project page and the
project list render the short description and the funders through
:html:`<f:format.html>` now, which fails without it. Fluid Styled Content and
most site packages define it, TYPO3 13 defines it for every site. Check it in
:guilabel:`Site Management > TypoScript > Active TypoScript`. See
:ref:`important-project-rich-text-uses-parse-func`.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/category-types':'~2.4.0' \
        'fgtclb/academic-projects':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796668`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`category_types` and
:guilabel:`academic_projects` there, in this order. Run the commands of this
page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-wizards:

Run the upgrade wizards
=======================

This extension adds no database column in 2.4. Both wizards require the
missing tables and columns of the installation to be added anyway.
:bash:`upgrade:run` adds them before it runs a wizard,
:guilabel:`Admin Tools > Upgrade > Upgrade Wizard` asks for it first.

Run the two wizards by name:

#.  ``academicProjects_pluginUpgradeWizard``, :guilabel:`Migrate
    academic_projects plugins from "list_type" to "CType".` It turns the
    :guilabel:`Insert plugin` content elements of the plugins
    ``academicprojects_projectlist`` and
    ``academicprojects_projectlistsingle`` into content elements of the type
    of the same name. It is unchanged in 2.4 and has nothing to do on an
    installation that ran it before.

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicProjects_pluginUpgradeWizard

#.  ``academicProjects_flexFormUpgradeWizard``, :guilabel:`Migrate the plugin
    settings of academic_projects project lists stored before 2.0.` It
    rewrites the settings of 1.x and early 2.0 project lists:
    ``settings.hide_completed_projects`` becomes ``settings.activeState``,
    ``settings.filter.options`` becomes ``settings.hideFilter`` and
    ``settings.sorting.options`` becomes ``settings.hideSorting``. A new
    setting that a save in the backend stored already is kept. Up to 2.3 the
    wizard migrated no record at all. See :ref:`important-1785796200` and
    :ref:`important-ace-847-academic-projects`.

    It reads the :guilabel:`Insert plugin` elements of 1.x as well, so the two
    wizards can run in either order, also together in one bare
    ``vendor/bin/typo3 upgrade:run``. Up to 2.3 it read the content elements
    the plugin wizard creates only, and a bare ``upgrade:run`` recorded it as
    done before it had anything to read. See
    :ref:`important-ace-923-academic-projects`.

    An installation that ran it before has it recorded as done, and TYPO3
    does not offer it again. TYPO3 13 marks a wizard undone with
    ``vendor/bin/typo3 upgrade:mark:undone academicProjects_flexFormUpgradeWizard``.
    TYPO3 12 has no such command: open
    :guilabel:`Admin Tools > Upgrade > Upgrade Wizard`, find the wizard under
    :guilabel:`Wizards marked as done` and choose :guilabel:`Mark undone`. For
    a wizard that was never marked done, the TYPO3 13 command fails with a
    message, which is harmless. Then run the wizard:

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicProjects_flexFormUpgradeWizard

    Running it again is safe. It only rewrites a FlexForm that still holds one
    of the old setting names, and it is offered only while one does.

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

The configuration of this extension changed shape in 2.4: one component per
content element, a separate component for the
:typoscript:`styles.content.getContent` override, the page TSconfig as a
selectable entry and the folder :file:`Configuration/TsConfig/` spelled
:file:`Configuration/TSconfig/`. The complete list of paths and names is in
:ref:`breaking-site-sets-and-static-templates-restructured`.

..  _upgrade-from-2-3-to-2-4-content-elements:

Make the content elements selectable
------------------------------------

The page TSconfig every installation includes hides the content elements
:guilabel:`Projects` and :guilabel:`Projects (selected)` now. The page TSconfig
of a component brings its element back. Enable them on every site that uses
them, with one of the two mechanisms and never with both:

*   **TYPO3 13 with site sets:** the site configuration depends on
    `fgtclb/academic-projects`. The set keeps the name it had in 2.3 and
    includes every component now, so a site that depends on it already needs
    no change. A site may name the component sets instead. It then adds
    `fgtclb/academic-projects-content-load` as well, or defines
    :typoscript:`styles.content.getContent` itself: the page template of the
    page type :guilabel:`Project` renders that object and fails without it.
*   **TYPO3 12, or TYPO3 13 without site sets:** in the page record of every
    site root, field :guilabel:`Include static Page TSconfig (from extensions)`
    on the tab :guilabel:`Resources`, add :guilabel:`Academic Projects: All
    components (academic_projects)`, or the entries of the components the site
    uses. The entry is new in 2.4, there is nothing to replace.

Existing content elements keep rendering in the frontend either way. In the
backend, an existing element on a page where its component is not enabled
keeps its type when it is saved. :guilabel:`academic_base` 2.4 shows the stored
type as the selected option, followed by :guilabel:`(not enabled on this
page)`. The warning of the changelog entry, that saving such an element writes
another :sql:`CType`, describes a state the 2.4 release of
:guilabel:`academic_base` no longer has.

..  _upgrade-from-2-3-to-2-4-static-template:

Complete the static template
----------------------------

This step concerns sites that include the TypoScript through a
:sql:`sys_template` record, which is every site on TYPO3 12.

The entry of 2.3, :guilabel:`Academic Projects`, keeps its stored value
`EXT:academic_projects/Configuration/TypoScript/` and is called
:guilabel:`Academic Projects: Shared plugin settings and page rendering
(academic_projects)` now. It no longer carries the
:typoscript:`styles.content.getContent` override that 2.3 included with it,
and the page template of the page type :guilabel:`Project` renders that
object. Without it, a project page fails.

Open the :sql:`sys_template` record of every site root, field
:guilabel:`Include TypoScript sets` on the tab :guilabel:`Advanced Options`,
and replace the entry with :guilabel:`Academic Projects: All components
(academic_projects)`. Alternatively add :guilabel:`Academic Projects: Content
load override (academic_projects)` next to it, or define
:typoscript:`styles.content.getContent` in the site package.

..  _upgrade-from-2-3-to-2-4-imports:

Adjust imports of shipped files
-------------------------------

A site package that imports a file of this extension by path checks every
:typoscript:`@import`. An import of a missing file is silent, so a wrong path
shows up as missing configuration, not as an error.

*   :file:`Configuration/TsConfig/` is :file:`Configuration/TSconfig/` now,
    and the wizard file
    :file:`Configuration/TsConfig/Wizards/NewContentElement.tsconfig` is
    :file:`Configuration/TSconfig/Full/page.tsconfig`.
*   :file:`Configuration/TsConfig/page.tsconfig` is gone. It carried nothing
    but a comment. The backend layout of the page type is part of the page
    TSconfig every installation includes now, see
    :ref:`important-1786969200`.
*   :file:`Configuration/TypoScript/setup.typoscript` no longer imports the
    :typoscript:`styles.content.getContent` override. Import
    :file:`Configuration/TypoScript/ContentLoad/setup.typoscript` where the
    site needs it. The old file
    :file:`Configuration/TypoScript/Content/ContentLoad.typoscript` is gone.
*   :file:`Configuration/Sets/AcademicProjects/constants.typoscript` and
    :file:`setup.typoscript` are gone. Import the files of
    :file:`Configuration/TypoScript/` instead.

..  _upgrade-from-2-3-to-2-4-label-overrides:

Move label overrides
--------------------

The templates read label overrides from the path the TYPO3 documentation
names. An override under :typoscript:`plugin.tx_academic_projects._LOCAL_LANG`
no longer has an effect. Move it to
:typoscript:`plugin.tx_academicprojects._LOCAL_LANG`, or to
:typoscript:`plugin.tx_academicprojects_<plugin>._LOCAL_LANG` for one plugin.
This includes the labels of the filter form. See
:ref:`important-label-overrides-use-the-documented-path` and
:ref:`important-1790383006`.

..  _upgrade-from-2-3-to-2-4-templates:

Adapt templates and assets
==========================

Version 2.4 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes, see :ref:`templates-override`. The paths below
are relative to :file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Pages/AcademicProject.html`,
            :file:`Partials/Project/Item.html`
        -   The short description, and on the page the funders as well, are
            rendered with :html:`<f:format.html>` instead of
            :html:`<f:format.raw>`, so that their links resolve.
        -   :ref:`important-project-rich-text-uses-parse-func`
    *   -   :file:`Pages/AcademicProject.html`
        -   The categories are read from
            :html:`{project.attributes.allCategoriesByType}`.
        -   :ref:`important-project-page-renders-categories`
    *   -   :file:`Pages/AcademicProject.html`
        -   The heading falls back to the title of the page record taken from
            :html:`{page}` or :html:`{data}`.
        -   :ref:`important-project-page-reads-the-page-record-from-page`
    *   -   :file:`Partials/Project/DemandCategories.html`
        -   Offers the category types
            :typoscript:`plugin.tx_academicprojects.filter.categoryTypes`
            names, in that order, and puts the ones after
            :typoscript:`filter.visibleCount` into a :guilabel:`More filters`
            section with labels of its own.
        -   :ref:`feature-1790383003`,
            :ref:`important-1790383006`
    *   -   :file:`Pages/AcademicProject.html`,
            :file:`Partials/Project/DemandActiveState.html`,
            :file:`Partials/Project/DemandCategories.html`,
            :file:`Partials/Project/DemandSorting.html`,
            :file:`Partials/Project/Item.html`,
            :file:`Partials/Project/ItemList.html`
        -   The translations pass the extension name :html:`AcademicProjects`
            instead of :html:`academic_projects`. A copy that keeps the old
            name does not see the label overrides of the previous section.
        -   :ref:`important-label-overrides-use-the-documented-path`
    *   -   :file:`Backend/Partials/PageLayout/Doktype30.html`
        -   Removed, and so is :file:`ext_typoscript_setup.typoscript`, which
            registered it. It was never rendered. The page module shows the
            categories of a project page through :guilabel:`category_types`
            now, which takes an override under
            :typoscript:`templates.fgtclb/category-types.<key>`.
        -   :ref:`important-1789660802`

A site package that shipped :file:`Academicproject.html` as the page template
of the page type renames it to :file:`AcademicProject.html`. The extension sets
:typoscript:`page.10.templateName` itself now. See
:ref:`important-1786968000`.

..  _upgrade-from-2-3-to-2-4-code:

Adapt your code
===============

*   Code that extends or replaces :php:`ProjectDemand`, the
    :php:`DemandFactory` or the :php:`ProjectRepository` takes the new flag
    :php:`showHiddenRecords` into account. No signature changed. See
    :ref:`important-ace-250-academic-projects`.
*   :php:`\FGTCLB\AcademicProjects\ViewHelpers\Format\ReplaceViewHelper`
    renders through :php:`render()` now instead of the static
    :php:`renderStatic()`, and declares :html:`content`, :html:`substring` and
    :html:`replacement` as :php:`mixed`. A subclass that overrides or calls
    :php:`renderStatic()` moves to :php:`render()`. Templates are not
    affected, see :ref:`important-1785882400`.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   The new content element wizard offers :guilabel:`Projects` and
    :guilabel:`Projects (selected)` on the pages of every site that uses them,
    and an existing element opens with its type.
*   A project page renders its template, its content and its categories, links
    in its short description and funders resolve, and its page module shows
    the categories of the page.
*   A project list of 1.x shows or hides completed projects as it did before,
    and its filter and sorting forms are shown or hidden as configured.
*   A list that hides a part of its form keeps the preset of that part when a
    visitor uses the rest of the form, a list of running projects with a
    hidden state select shows running projects only, see
    :ref:`important-hidden-project-form-parts-keep-their-presets`.
*   A list shows a project only when it carries every selected category, as
    before, and not when it carries only a subcategory of one. The
    description of the field says so now, see
    :ref:`important-project-categories-description`.
*   A list filtered by :guilabel:`Active` lists projects without an end date
    now, so a paginated list can grow by some entries. See
    :ref:`important-projects-without-end-date-are-active`.
*   On a site language with :yaml:`fallbackType: free`, the
    :guilabel:`Projects (selected)` content element shows the translated
    projects. See :ref:`important-ace-341-academic-projects`.
