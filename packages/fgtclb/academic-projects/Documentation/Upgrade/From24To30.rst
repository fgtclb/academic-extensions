..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 of :guilabel:`academic_projects` runs on TYPO3 13 and 14 only. The
project page renders inside the layout of the site and carries its content
itself, so the content load set of 2.4 is gone. The department category type of
this extension is renamed to `project_department`, the templates carry speaking
classes in place of the classes of a theme, the icons are replaced, and the
controller is final. Work through the sections in order. Skip a step that does
not apply to the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_projects` 3.0 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, which
    :guilabel:`academic_base` 3.0 requires in place of 1.x. Composer updates it
    on its own. A classic installation updates
    :guilabel:`environment_state_manager` before it updates
    :guilabel:`academic_base`.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.
*   :composer:`fgtclb/category-types` 3.0, the same release as this extension.

These changes have to ship in the same deployment as the update, because a
site, a request or a command fails until they are made:

*   A site that depends on `fgtclb/academic-projects-content-load` answers
    every page with HTTP 500, see
    :ref:`upgrade-from-2-4-to-3-0-content-load`.
*   A :file:`Configuration/CategoryTypes.yaml` of the site package that changes
    or declares the type `department` of the group :yaml:`projects` stops
    TYPO3 while it loads the category types, see
    :ref:`upgrade-from-2-4-to-3-0-department`.
*   A subclass or an XCLASS of the project controller stops with a fatal
    error, and so does code that names the renamed sorting constant, see
    :ref:`upgrade-from-2-4-to-3-0-code`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
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

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/category-types':'~3.0.0' \
        'fgtclb/academic-projects':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`category_types` and
:guilabel:`academic_projects` there, in this order. Run the commands of this
page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

This extension changes no column in 3.0. The comparison applies the changes of
the index definitions TYPO3 13.4.35 and 14.3.7 bring.

..  _upgrade-from-2-4-to-3-0-wizards:

Run the upgrade wizards
=======================

#.  ``academicProjects_pluginUpgradeWizard``, :guilabel:`Migrate
    academic_projects plugins from "list_type" to "CType".`, on TYPO3 13,
    before the site moves to TYPO3 14. It turns the :guilabel:`Insert plugin`
    content elements of the plugins ``academicprojects_projectlist`` and
    ``academicprojects_projectlistsingle`` into content elements of the type
    of the same name, and it reads :sql:`tt_content.list_type`, which TYPO3 14
    no longer has. See :ref:`breaking-plugins-require-ctype-on-typo3-v14`.

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicProjects_pluginUpgradeWizard

    An installation that ran it with 2.4 has nothing left to migrate, and
    TYPO3 offers it only while an element of the old shape is left. Do not
    postpone it to TYPO3 14: once the database comparison of TYPO3 14 has
    removed the column, the wizard finds nothing to do and is recorded as done,
    and nothing records any more which plugin such a content element was.
    Configuration of the site that matches these plugins on :sql:`list_type`
    matches on :sql:`CType` instead.

A wizard that TYPO3 recorded as done is offered again once it is marked
undone. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone <identifier>``.

Version 3.0 adds no upgrade wizard for this extension. The department category
type is moved by a console command, see
:ref:`upgrade-from-2-4-to-3-0-department`.

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. For this extension it reports a dependency on
the removed content load set and an XCLASS of the controller, but neither a
subclass that is registered another way nor the category type file. Run it on
the staging copy right after the package update, and fix what it reports
before the deployment:

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

..  _upgrade-from-2-4-to-3-0-content-load:

Remove the content load set
---------------------------

The project page renders the content of its main column from the variable
:typoscript:`projectContent` of its own page object now. The set
`fgtclb/academic-projects-content-load`, its static template
:guilabel:`Academic Projects: Content load override (academic_projects)` and
the file :file:`Configuration/TypoScript/ContentLoad/setup.typoscript` are
removed, and no academic extension defines
:typoscript:`styles.content.getContent` any more. See
:ref:`breaking-projects-content-load-set-removed`.

#.  Remove `fgtclb/academic-projects-content-load` from the
    :yaml:`dependencies` of every site configuration and of every set of the
    site package, in the same deployment as the update. A site that depends on
    the aggregate `fgtclb/academic-projects` needs no change.
#.  Remove the static template :guilabel:`Academic Projects: Content load
    override (academic_projects)` from every :sql:`sys_template` record, and an
    :typoscript:`@import` of the removed file from the site package.
#.  Move a customisation of :typoscript:`styles.content.getContent` that was
    meant for project pages to :typoscript:`page.10.variables.projectContent`,
    inside the condition on the project page type, see
    :ref:`project-page-content`.
#.  A template of the site package that renders
    :typoscript:`styles.content.getContent` defines the object itself:

    ..  code-block:: typoscript

        styles.content.getContent < styles.content.get
        styles.content.getContent.select.where = {#colPos}=0

:bash:`academic:upgrade:check` reports a site that depends on the removed set as
:bash:`unavailable-set`, the removed static template as
:bash:`static-template` and an import of the removed file as
:bash:`typoscript-import`.

..  _upgrade-from-2-4-to-3-0-department:

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
    the update. Not before it: 2.4 has no `project_department` to change.
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

..  _upgrade-from-2-4-to-3-0-route-enhancers:

Check own route enhancers
-------------------------

The project lists answer a filter submission with a redirect to a URL that
carries the selection. A route enhancer of the site for these plugins must not
declare :yaml:`defaults` for the sorting: the redirect for the default sorting
would otherwise end on the page URL without arguments, where the preset of the
content element applies again. Remove such :yaml:`defaults`, or import the
enhancers of :file:`Configuration/Routes/List.yaml` instead, see
:ref:`feature-1790226101` and :ref:`feature-1791043403`.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-4-to-3-0-stylesheets:

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

..  _upgrade-from-2-4-to-3-0-icons:

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

..  _upgrade-from-2-4-to-3-0-page:

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
*   A path the site set at the key `100` inside the condition on the project
    page type moves to `50`, or to a key above it to win over the extension.

..  _upgrade-from-2-4-to-3-0-overrides:

Compare template overrides
--------------------------

Version 3.0 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes, see :ref:`templates-override`. The paths below
are relative to :file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Pages/AcademicProject.html`
        -   Renders the layout of the site and the partials below
            :file:`Project/Page/`, and the content from
            :html:`{projectContent -> f:format.raw()}` in place of
            :typoscript:`styles.content.getContent`.
        -   :ref:`upgrade-from-2-4-to-3-0-page`,
            :ref:`upgrade-from-2-4-to-3-0-content-load`
    *   -   :file:`Templates/Project/List.html`
        -   Renders the header of the content element while
            :typoscript:`renderContentElementHeader` is switched on.
        -   :ref:`feature-1790440964`
    *   -   :file:`Partials/Project/SortingAndFilters.html`
        -   Renders the new partials :file:`Project/ActiveFilters.html` and
            :file:`Project/ResultCount.html`.
        -   :ref:`feature-1790946475`
    *   -   :file:`Partials/Project/Item.html`
        -   Renders the image through :file:`Academic/Image.html` of
            :guilabel:`academic_base`, the category icons with the
            :html:`ab:icon` ViewHelper, the title of an own category type, and
            the state of the project when the content element asks for it.
        -   :ref:`upgrade-from-2-4-to-3-0-stylesheets`,
            :ref:`upgrade-from-2-4-to-3-0-icons`,
            :ref:`feature-1791035984`,
            :ref:`feature-project-active-state-badge`
    *   -   :file:`Partials/Project/DemandCategories.html`
        -   Labels an own category type by its title.
        -   :ref:`feature-1791035984`
    *   -   :file:`Partials/Project/Header.html`
        -   Takes the class of the heading as :html:`class` in place of
            :html:`positionClass`.
        -   :ref:`upgrade-from-2-4-to-3-0-stylesheets`
    *   -   Every file above, and
            :file:`Partials/Project/DemandActiveState.html`,
            :file:`Partials/Project/DemandSorting.html`,
            :file:`Partials/Project/ItemList.html`
        -   Speaking classes in place of the classes of a theme.
        -   :ref:`upgrade-from-2-4-to-3-0-stylesheets`

The upgrade check with the extension key and a site reports an override of a
file the extension no longer ships, see
:ref:`upgrade-from-2-4-to-3-0-upgrade-check`.

..  _upgrade-from-2-4-to-3-0-code:

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

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`. Run it again after each
    configuration step, and in the deployment pipeline.
*   The content elements are offered as :guilabel:`Projects` and
    :guilabel:`Selected Projects` now, see
    :ref:`important-content-element-titles-and-descriptions`.
*   Every page of a site answers, none with HTTP 500.
*   :bash:`vendor/bin/typo3 academic:projects:department:migrate` lists no
    category left to decide on.
*   A project page renders inside the layout of the site, with its content and
    its categories, the department among them.
*   The project lists look as the site intends, with the speaking classes, and
    their department filter offers the departments of projects only.
