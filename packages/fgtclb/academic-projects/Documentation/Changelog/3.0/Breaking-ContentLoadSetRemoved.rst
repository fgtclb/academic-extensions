..  _breaking-projects-content-load-set-removed:

==================================================================
Breaking: Project pages render their content without a global path
==================================================================

Description
===========

The page template of the page type :guilabel:`Academic project` rendered the
content elements of the page through the global TypoScript object
:typoscript:`styles.content.getContent`. Only the opt-in set
`fgtclb/academic-projects-content-load`, its static template and the static
template :guilabel:`Academic Projects: All components`, which included it,
defined it, for every page of the site. A site on the component sets or the
component static templates without them got an exception on every project
page:

..  code-block:: text

    No Content Object definition found at TypoScript object path "styles.content.getContent"

From 3.0 on, the page object of the project page type carries the content
itself, as the variable :typoscript:`projectContent`:

..  code-block:: typoscript

    [page && traverse(page, "doktype") == 30]
      page.10.variables.projectContent = CONTENT
      page.10.variables.projectContent {
        table = tt_content
        select {
          orderBy = sorting, uid
          where = {#colPos}=0
        }
      }
    [END]

The page template renders it as
:html:`{projectContent -> f:format.raw()}`, on a :typoscript:`FLUIDTEMPLATE`
and on a :typoscript:`PAGEVIEW` page object alike. Every set of this extension
delivers it.

The site wide override is not needed any more, and it is removed:

*   the set `fgtclb/academic-projects-content-load`;
*   the dependency on it in the aggregate set `fgtclb/academic-projects`;
*   the static template
    :guilabel:`Academic Projects: Content load override (academic_projects)`,
    stored as `EXT:academic_projects/Configuration/TypoScript/ContentLoad`,
    and the entry for it in
    :guilabel:`Academic Projects: All components (academic_projects)`;
*   the file
    :file:`EXT:academic_projects/Configuration/TypoScript/ContentLoad/setup.typoscript`.

The program page went the same way before, and the partner and project pages
followed together, so from 3.0 on no academic extension defines
:typoscript:`styles.content.getContent`.

Impact
======

*   A site configuration that names `fgtclb/academic-projects-content-load` as
    a dependency, directly or through a set of the site package, fails
    completely: TYPO3 answers every page of the site with HTTP 500 and the
    message *"Site <identifier> depends on unavailable sets: ..."*, which
    names the removed set, or the set of the site package that depends on it.
    A site that depends on the aggregate
    `fgtclb/academic-projects` is not affected.
*   :typoscript:`styles.content.getContent` is no longer defined. A template of
    the site package that renders it through :html:`f:cObject` throws the
    exception above.
*   A template override of :file:`AcademicProject.html` that still renders
    :typoscript:`styles.content.getContent` throws the same exception.
*   A customisation of :typoscript:`styles.content.getContent`, for example
    one that slides the content from the parent pages, no longer reaches
    project pages. The same holds for a customisation of
    :typoscript:`styles.content.get` that was parsed before the override:
    the override copied it into :typoscript:`styles.content.getContent`.
*   A :sql:`sys_template` record that selects the removed static template, or
    imports the removed file, gets nothing for it, without a message.

Affected Installations
======================

Installations that name the content load set or select its static template,
that render :typoscript:`styles.content.getContent` in a template of their own,
or that customised that object for project pages.

The console command :bash:`academic:upgrade:check` of
:php:`EXT:academic_base` reports each of the stored references: a site that
depends on the removed set as :bash:`unavailable-set`, the removed static
template as :bash:`static-template` and an :typoscript:`@import` of the removed
file in a TypoScript record as :bash:`typoscript-import`.

Migration
=========

*   Remove `fgtclb/academic-projects-content-load` from the `dependencies` of
    the site configuration and of every set of the site package. The aggregate
    `fgtclb/academic-projects`, or the component sets, deliver everything a
    project page needs.
*   Remove the static template from the :sql:`sys_template` record, and an
    :typoscript:`@import` of the removed file from a site package.
*   A template override of :file:`AcademicProject.html` renders
    :html:`{projectContent -> f:format.raw()}` instead of the
    :html:`f:cObject` call, or overrides the partial
    :file:`Project/Page/Content.html` alone.
*   Move a customisation of :typoscript:`styles.content.getContent` for
    project pages to :typoscript:`page.10.variables.projectContent`, inside the
    same condition - see :ref:`project-page-content`.
*   A site package that renders :typoscript:`styles.content.getContent` in a
    template of its own defines the object itself, for example from
    :typoscript:`styles.content.get`, which TYPO3 defines on every site:

    ..  code-block:: typoscript

        styles.content.getContent < styles.content.get
        styles.content.getContent.select.where = {#colPos}=0

..  index:: Frontend, TypoScript, ext:academic_projects
