..  _upgrade-from-2-3-to-3-0:

=========================
Upgrading from 2.3 to 3.0
=========================

This page takes an installation of :guilabel:`academic_projects` 2.3 straight
to 3.0, without the step to 2.4. Version 2.4 brings the upgrade wizards, the
selectable content elements and new paths of the shipped files. Version 3.0
runs on TYPO3 13 and 14 only, renders the project page inside the layout of the
site, renames the department category type of this extension to
`project_department`, carries speaking classes, replaces the icons and makes
the controller final. The page leaves out the 2.4 state that 3.0 replaces: the
content load component, which the :ref:`2.4 changelog entry on the restructured
configuration <breaking-site-sets-and-static-templates-restructured>` names.
Work through the sections in order. Skip a step that does not apply to the
installation.

..  _upgrade-from-2-3-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_projects` 3.0 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, new since 2.4, which
    :guilabel:`academic_base` 3.0 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.
*   :composer:`fgtclb/category-types` 3.0, the same release as this extension.

These changes have to ship in the same deployment as the update, because a
site, a request or a command fails until they are made:

*   A site without site sets includes the page TSconfig of the components, or
    the content elements are not offered, see
    :ref:`upgrade-from-2-3-to-3-0-content-elements`.
*   A :file:`Configuration/CategoryTypes.yaml` of the site package that changes
    or declares the type `department` of the group :yaml:`projects` stops
    TYPO3 while it loads the category types, see
    :ref:`upgrade-from-2-3-to-3-0-department`.
*   A subclass or an XCLASS of the project controller stops with a fatal
    error, and so does code that names the renamed sorting constant, see
    :ref:`upgrade-from-2-3-to-3-0-code`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-3-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.3, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete. The content element wizard
``academicProjects_pluginUpgradeWizard`` of this extension reads the column
:sql:`tt_content.list_type`, which TYPO3 14 removes, so it only runs on
TYPO3 13. The same holds for the content element wizards of
:guilabel:`academic_jobs`, :guilabel:`academic_persons` and
:guilabel:`academic_persons_edit` when the installation also has them. Move to
TYPO3 14.3.7 or later once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-3-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/category-types':'~3.0.0' \
        'fgtclb/academic-projects':'~3.0.0'

On a classic installation, install :guilabel:`environment_state_manager` 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`category_types` and
:guilabel:`academic_projects` there, in this order. Run the commands of this
page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

This extension changes no column in 2.4 or 3.0. The comparison applies the
changes of the index definitions TYPO3 13.4.35 and 14.3.7 bring. Run it all the
same before the wizards of the next section, which need every table and column
of the installation in place.

..  _upgrade-from-2-3-to-3-0-wizards:

Run the upgrade wizards
=======================

Run the two wizards by name on TYPO3 13.

#.  ``academicProjects_pluginUpgradeWizard``, :guilabel:`Migrate
    academic_projects plugins from "list_type" to "CType".` It turns the
    :guilabel:`Insert plugin` content elements of the plugins
    ``academicprojects_projectlist`` and ``academicprojects_projectlistsingle``
    into content elements of the type of the same name. It reads
    :sql:`tt_content.list_type`, which TYPO3 14 no longer has, so it runs before
    the site moves to TYPO3 14. Once the database comparison of TYPO3 14 has
    removed the column, the wizard finds nothing to do and is recorded as done,
    and nothing records any more which plugin such a content element was.
    Configuration of the site that matches these plugins on :sql:`list_type`
    matches on :sql:`CType` instead. See
    :ref:`breaking-plugins-require-ctype-on-typo3-v14`.

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

    An installation that ran it with 2.3 has it recorded as done, and TYPO3
    does not offer it again. Mark it undone first. For a wizard that was never
    recorded as done, the command fails with a message, which is harmless.
    Then run the wizard:

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:mark:undone academicProjects_flexFormUpgradeWizard
        vendor/bin/typo3 upgrade:run academicProjects_flexFormUpgradeWizard

    Running it again is safe. It only rewrites a FlexForm that still holds one
    of the old setting names, and it is offered only while one does.

A wizard that TYPO3 recorded as done is offered again once it is marked
undone. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone <identifier>``.

The department category type is moved by a console command, see
:ref:`upgrade-from-2-3-to-3-0-department`.

..  _upgrade-from-2-3-to-3-0-configuration:

Adapt the configuration
=======================

The configuration of this extension is delivered per component since 2.4: one
component per content element, the page TSconfig as a selectable entry and the
folder :file:`Configuration/TsConfig/` spelled :file:`Configuration/TSconfig/`.
The complete list of paths and names is in the 2.4 changelog entry the
introduction of this page links. The content load component that entry names
was removed again in 3.0, see :ref:`upgrade-from-2-3-to-3-0-content`.

..  _upgrade-from-2-3-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. For this extension it reports an XCLASS of
the controller and a stored path of 2.3 that no longer exists, but neither a
subclass that is registered another way nor the category type file. Run it on
the staging copy right after the package update, and again once the steps of
this section are done:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also compares the override folders of the
site with the shipped files and reports an override of a file the extension no
longer ships:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_projects --site=<site identifier>

Each finding, and what to do about it, is described in the `upgrade check
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/UpgradeCheck/Index.html>`__
of :guilabel:`academic_base`.

..  _upgrade-from-2-3-to-3-0-content-elements:

Make the content elements selectable
------------------------------------

The page TSconfig every installation includes hides the content elements
:guilabel:`Projects` and :guilabel:`Selected Projects`. The page TSconfig of a
component brings its element back. Enable them on every site that uses them,
with one of the two mechanisms and never with both:

*   **With site sets:** the site configuration depends on
    `fgtclb/academic-projects`. The set keeps the name it had in 2.3 and
    includes every component, so a site that depends on it already needs no
    change. A site may name the component sets instead.
*   **Without site sets:** in the page record of every site root, field
    :guilabel:`Include static Page TSconfig (from extensions)` on the tab
    :guilabel:`Resources`, add :guilabel:`Academic Projects: All components
    (academic_projects)`, or the entries of the components the site uses.

Until a component is included, an existing content element of it keeps
rendering and keeps its type: :guilabel:`academic_base` shows the stored type
in the field :guilabel:`Type` followed by
:guilabel:`(not enabled on this page)`, and saving keeps it. The warning of the
2.4 changelog entry that saving such a record rewrites its type describes the
state before a fix that :guilabel:`academic_base` 2.4 carries as well, see
`its changelog entry <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/2.4/Important-HiddenContentTypeStaysSelectable.html>`__.
A content element that a page TSconfig of your own already turned into another
type is not repaired. Correct it by hand from its record history.

..  _upgrade-from-2-3-to-3-0-static-template:

Check the static template
-------------------------

This step concerns sites that include the TypoScript through a
:sql:`sys_template` record.

The entry of 2.3, :guilabel:`Academic Projects`, keeps its stored value
`EXT:academic_projects/Configuration/TypoScript/` and is called
:guilabel:`Academic Projects: Shared plugin settings and page rendering
(academic_projects)` now. It keeps working, but it no longer defines
:typoscript:`styles.content.getContent`, see
:ref:`upgrade-from-2-3-to-3-0-content`.
:guilabel:`Academic Projects: All components (academic_projects)` carries the
same TypoScript, and is the entry to select for a new site.

..  _upgrade-from-2-3-to-3-0-imports:

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
    TSconfig every installation includes, see :ref:`important-1786969200`.
*   :file:`Configuration/TypoScript/Content/ContentLoad.typoscript` is gone,
    without a successor, see :ref:`upgrade-from-2-3-to-3-0-content`.
*   :file:`Configuration/Sets/AcademicProjects/constants.typoscript` and
    :file:`setup.typoscript` are gone. Import the files of
    :file:`Configuration/TypoScript/` instead.

:bash:`academic:upgrade:check` reports an import of a missing file in a
TypoScript record as :bash:`typoscript-import` and in page TSconfig as
:bash:`tsconfig-import`.

..  _upgrade-from-2-3-to-3-0-content:

Replace the global content object
---------------------------------

Version 2.3 defined :typoscript:`styles.content.getContent` for every page of
the site, through :file:`Configuration/TypoScript/setup.typoscript`, and the
project page rendered it. In 3.0 the project page renders the content of its
main column from the variable :typoscript:`projectContent` of its own page
object, and no academic extension defines
:typoscript:`styles.content.getContent` any more. See
:ref:`breaking-projects-content-load-set-removed`.

*   Move a customisation of :typoscript:`styles.content.getContent` that was
    meant for project pages to :typoscript:`page.10.variables.projectContent`,
    inside the condition on the project page type, see
    :ref:`project-page-content`.
*   A template of the site package that renders
    :typoscript:`styles.content.getContent` defines the object itself:

    ..  code-block:: typoscript

        styles.content.getContent < styles.content.get
        styles.content.getContent.select.where = {#colPos}=0

..  _upgrade-from-2-3-to-3-0-label-overrides:

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

..  _upgrade-from-2-3-to-3-0-department:

Rename the department category type
-----------------------------------

The department type of this extension is `project_department` now, because
:guilabel:`academic_programs` declares a type `department` as well and a
category stores its type without the group. Its label key and its icon
identifier follow. See
:ref:`breaking-projects-project-department-category-type`.

#.  Replace `department` with `project_department` in every
    :file:`Configuration/CategoryTypes.yaml` of the site that changes or
    removes the type of the group :yaml:`projects`, in the same deployment as
    the update. Not before it: 2.3 has no `project_department` to change.
#.  After the update and the cache flush, move the stored categories:

    ..  code-block:: bash

        vendor/bin/typo3 academic:projects:department:migrate

    The command lists the categories it cannot decide on. Open each of them in
    the backend and set its type. It can be run again. A site that removed the
    projects department in its :file:`CategoryTypes.yaml` does not run it.
#.  Replace `department` with `project_department` in
    :typoscript:`plugin.tx_academicprojects.filter.categoryTypes` and in the
    templates of the site. Search the site package for
    `sys_category.projects.department` and
    `category_types.projects.department` as well.
#.  Flush the page cache.

..  _upgrade-from-2-3-to-3-0-route-enhancers:

Check own route enhancers
-------------------------

The project lists answer a filter submission with a redirect to a URL that
carries the selection. A route enhancer of the site for these plugins must not
declare :yaml:`defaults` for the sorting: the redirect for the default sorting
would otherwise end on the page URL without arguments, where the preset of the
content element applies again. Remove such :yaml:`defaults`, or import the
enhancers of :file:`Configuration/Routes/List.yaml` instead, see
:ref:`feature-1790226101` and :ref:`feature-1791043403`.

..  _upgrade-from-2-3-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-3-to-3-0-stylesheets:

Style the new markup
--------------------

The templates of this extension carry speaking classes now, such as `ace-item`,
`ace-list`, `ace-attribute`, `ace-label` and `ace-value`, in place of the card,
list group and utility classes of a theme. The extension ships no styles for
them. Move the selectors of the site stylesheet from the old classes to the new
ones, the changelog entry lists them side by side, see
:ref:`breaking-projects-speaking-frontend-classes`. :ref:`styling` shows the
markup of every view and links the stylesheet partial the development instances
of the academic extensions use, :ref:`styling-example`.

The images of the project list and the project page render as a
:html:`<picture>` through the image partial of :guilabel:`academic_base`. CSS
that selects the image as a direct child of its container adjusts to the
wrapper. A plugin view whose partial root paths the site replaced completely
needs the path of :guilabel:`academic_base` below its own:

..  code-block:: typoscript

    plugin.tx_academicprojects.view.partialRootPaths {
        -1 = EXT:academic_base/Resources/Private/Partials/
    }

See :ref:`breaking-projects-images-render-as-picture`.

..  _upgrade-from-2-3-to-3-0-icons:

Replace the icons
-----------------

The project page type and the two content elements have icons of their own,
:php:`tx-academicprojects-doktype-project` and
:php:`tx-academicprojects-plugin-projects`, in place of the core icon
:php:`actions-code-merge`. CSS that addressed them through that identifier names
the new one. The category type icons keep their identifiers, but are new
drawings from new files and render as an inline :html:`<svg>` in the frontend,
so CSS that addressed an :html:`<img>` addresses the :html:`<svg>` now, and a
path to a file below :file:`Resources/Public/Icons/CategoryTypes/` points at the
new file. See
:ref:`breaking-projects-category-icons-follow-the-colour-scheme` and
:ref:`important-projects-category-icons-come-from-the-frontend-icon-registry`.

..  _upgrade-from-2-3-to-3-0-page:

Render project pages in the site layout
---------------------------------------

The page template of the project page type renders the section :html:`Main` of
the layout :file:`Default` of the site package, through five partials below
:file:`Project/Page/`. A project page shows the header, the navigation and the
footer of the site now. See
:ref:`breaking-project-page-renders-inside-the-site-layout`.

*   A site package whose layout for project pages has another name sets
    :typoscript:`plugin.tx_academicprojects.page.layout`, see
    :ref:`project-page-layout`.
*   An override of :file:`Pages/AcademicProject.html` made to change one part
    moves that change to the matching partial below :file:`Project/Page/`, see
    :ref:`project-page-partials`, and the override goes.
*   A site package that shipped :file:`Academicproject.html` as the page
    template of the page type renames it to :file:`AcademicProject.html`. The
    extension sets :typoscript:`page.10.templateName` itself. See
    :ref:`important-1786968000`.

..  _upgrade-from-2-3-to-3-0-overrides:

Compare template overrides
--------------------------

Versions 2.4 and 3.0 change these templates and partials. A copy of one of them
in a site package keeps rendering the copy, so compare each copy with the
shipped file of 3.0 and take over the changes, see :ref:`templates-override`.
The paths below are relative to :file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Pages/AcademicProject.html`
        -   Renders the layout of the site and the partials below
            :file:`Project/Page/`, the content from
            :html:`{projectContent -> f:format.raw()}` in place of
            :typoscript:`styles.content.getContent`, the categories from
            :html:`{project.attributes.allCategoriesByType}`, and the page
            record from :html:`{page}` or :html:`{data}`.
        -   :ref:`upgrade-from-2-3-to-3-0-page`,
            :ref:`upgrade-from-2-3-to-3-0-content`,
            :ref:`important-project-page-renders-categories`,
            :ref:`important-project-page-reads-the-page-record-from-page`
    *   -   :file:`Pages/AcademicProject.html`,
            :file:`Partials/Project/Item.html`
        -   The short description, and on the page the funders as well, are
            rendered with :html:`<f:format.html>` instead of
            :html:`<f:format.raw>`, so that their links resolve.
        -   :ref:`important-project-rich-text-uses-parse-func`
    *   -   :file:`Templates/Project/List.html`
        -   Renders the header of the content element while
            :typoscript:`renderContentElementHeader` is switched on.
        -   :ref:`feature-1790440964`
    *   -   :file:`Partials/Project/SortingAndFilters.html`
        -   Renders the new partials :file:`Project/ActiveFilters.html` and
            :file:`Project/ResultCount.html`, and a submit button inside
            :html:`<noscript>` for visitors without JavaScript.
        -   :ref:`feature-1790946475`,
            :ref:`important-ace-924-academic-projects`
    *   -   :file:`Partials/Project/Item.html`
        -   Renders the image through :file:`Academic/Image.html` of
            :guilabel:`academic_base`, the category icons with the
            :html:`ab:icon` ViewHelper, the title of an own category type, and
            the state of the project when the content element asks for it.
        -   :ref:`upgrade-from-2-3-to-3-0-stylesheets`,
            :ref:`upgrade-from-2-3-to-3-0-icons`,
            :ref:`feature-1791035984`,
            :ref:`feature-project-active-state-badge`
    *   -   :file:`Partials/Project/DemandCategories.html`
        -   Offers the category types
            :typoscript:`plugin.tx_academicprojects.filter.categoryTypes`
            names, in that order, puts the ones after
            :typoscript:`filter.visibleCount` into a :guilabel:`More filters`
            section, and labels an own category type by its title.
        -   :ref:`feature-1790383003`,
            :ref:`feature-1791035984`
    *   -   :file:`Partials/Project/Header.html`
        -   Takes the class of the heading as :html:`class` in place of
            :html:`positionClass`.
        -   :ref:`upgrade-from-2-3-to-3-0-stylesheets`
    *   -   Every file above, and
            :file:`Partials/Project/DemandActiveState.html`,
            :file:`Partials/Project/DemandSorting.html`,
            :file:`Partials/Project/ItemList.html`
        -   Speaking classes in place of the classes of a theme. The
            translations pass the extension name :html:`AcademicProjects`
            instead of :html:`academic_projects`, otherwise they miss the
            label overrides of the configuration step.
        -   :ref:`upgrade-from-2-3-to-3-0-stylesheets`,
            :ref:`upgrade-from-2-3-to-3-0-label-overrides`
    *   -   :file:`Backend/Partials/PageLayout/Doktype30.html`
        -   Removed, and so is :file:`ext_typoscript_setup.typoscript`, which
            registered it. It was never rendered. The page module shows the
            categories of a project page through :guilabel:`category_types`,
            which takes an override under
            :typoscript:`templates.fgtclb/category-types.<key>`.
        -   :ref:`important-1789660802`

The upgrade check with the extension key and a site reports an override of a
file the extension no longer ships, see
:ref:`upgrade-from-2-3-to-3-0-upgrade-check`.

..  _upgrade-from-2-3-to-3-0-code:

Adapt your code
===============

*   :php:`\FGTCLB\AcademicProjects\Controller\ProjectController` is final. A
    subclass, and an XCLASS of it, stop with a fatal error. Remove them, and
    the :php:`configurePlugin()` call or the XCLASS registration that points at
    them, in the same deployment as the update. Move what they did to a
    listener of the project events, see :ref:`breaking-1791043407` and
    :ref:`developers`.
*   :php:`SortingOptions::SORT_BY_STARTDATE_desc` is renamed to
    :php:`SortingOptions::SORT_BY_STARTDATE_DESC`, without an alias. Its value
    and every stored sorting stay as they are. See
    :ref:`breaking-start-date-sorting-constant-renamed`.
*   Code that extends or replaces :php:`ProjectDemand`, the
    :php:`DemandFactory` or the :php:`ProjectRepository` takes the flag
    :php:`showHiddenRecords` into account, new since 2.4. See
    :ref:`important-ace-250-academic-projects`.
*   :php:`\FGTCLB\AcademicProjects\ViewHelpers\Format\ReplaceViewHelper`
    renders through :php:`render()` instead of the static
    :php:`renderStatic()`, and declares :html:`content`, :html:`substring` and
    :html:`replacement` as :php:`mixed`. A subclass that overrides or calls
    :php:`renderStatic()` moves to :php:`render()`. Templates are not
    affected, see :ref:`important-1785882400`.

..  _upgrade-from-2-3-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`. Run it again after each
    configuration step, and in the deployment pipeline.
*   :bash:`vendor/bin/typo3 academic:projects:department:migrate` lists no
    category left to decide on.
*   The new content element wizard offers :guilabel:`Projects` and
    :guilabel:`Selected Projects` on the pages of every site that uses them,
    see :ref:`important-content-element-titles-and-descriptions`, and an
    existing element opens with its type.
*   A project page renders inside the layout of the site, with its content and
    its categories, the department among them. Links in its short description
    and funders resolve, and its page module shows the categories of the page.
*   The project lists look as the site intends, with the speaking classes. A
    project list of 1.x shows or hides completed projects as it did before, and
    its filter and sorting forms are shown or hidden as configured.
*   A list that hides a part of its form keeps the preset of that part when a
    visitor uses the rest of the form, a list of running projects with a
    hidden state select shows running projects only, see
    :ref:`important-hidden-project-form-parts-keep-their-presets`.
*   A list shows a project only when it carries every selected category, as
    before, and not when it carries only a subcategory of one. The
    description of the field says so now, see
    :ref:`important-project-categories-description`.
*   A list keeps its preset categories of the category types its filter does
    not offer, see
    :ref:`important-project-list-keeps-default-categories-of-other-types`.
*   A list filtered by :guilabel:`Active` lists projects without an end date
    now, so a paginated list can grow by some entries. See
    :ref:`important-projects-without-end-date-are-active`.
*   On a site language with :yaml:`fallbackType: free`, the
    :guilabel:`Selected Projects` content element shows the translated
    projects. See :ref:`important-ace-341-academic-projects`.
