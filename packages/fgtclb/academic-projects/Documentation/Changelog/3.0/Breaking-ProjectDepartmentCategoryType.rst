..  _breaking-projects-project-department-category-type:

=================================================
Breaking: The projects department type is renamed
=================================================

Description
===========

`academic_projects` and `academic_programs` both declared a category type
`department`. A category stores the type without its group, so with both
extensions installed the type select of a category offered two items with the
value `department`: after saving, the backend selected the first one, whichever
the editor had picked, and the project list offered the departments of study
programs in its department filter as well.

The department type of this extension is now `project_department`. Its label
key follows, from `sys_category.projects.department` to
`sys_category.projects.project_department`, and so does its icon identifier,
from `category_types.projects.department` to
`category_types.projects.project_department`. The type of `academic_programs`
keeps the identifier `department`.

`EXT:category_types` now refuses an identifier that two groups declare, see
its changelog. The previous versions of the two extensions, installed
together, were such a case.

The command `academic:projects:department:migrate` moves the stored
categories. It is a console command rather than an upgrade wizard, because no
new upgrade wizard is added while TYPO3 v13 is supported.

Impact
======

A :file:`Configuration/CategoryTypes.yaml` of a project that changes the
projects department with :yaml:`useExisting: true`, or declares `department`
in the group :yaml:`projects` again, stops TYPO3 while it loads the category
types: the first no longer finds the type it changes, the second collides with
the department of `academic_programs` when that is installed. The backend, the
frontend and the command line fail alike. A :yaml:`remove: true` of the old
type removes nothing any more, so the projects department is back, as
`project_department`.

Until the command has run, a stored project department keeps the type
`department`. With `academic_programs` installed it is a department of study
programs; without it, it has a type no extension declares. Either way the
project page, the project list items and the department filter of the project
list no longer show it.

The setting :typoscript:`plugin.tx_academicprojects.filter.categoryTypes`
naming `department` offers no department filter any more, as for every
identifier the group does not have. A filter link from before the update names
the filter `department` too, so it no longer filters by department.

A template or label override that names the old label key or icon identifier
reaches nothing.

Affected installations
======================

Every installation of `academic_projects` that stores categories of the
projects department type, or that names `department` in the filter setting, in
a template, a label override or a :file:`Configuration/CategoryTypes.yaml`.

Migration
=========

1.  Replace `department` with `project_department` in every
    :file:`Configuration/CategoryTypes.yaml` that changes or removes the type
    of the group :yaml:`projects`, in the same deployment as the update, so
    the change is in place before the first request or command runs on the
    new code. Otherwise TYPO3, and with it the command below, stops. Not
    before the update: the previous version has no `project_department` to
    change.

    A project that removed the projects department removes
    `project_department` this way, and does not run the command below: its
    department categories are those of study programs, and the command would
    move the ones on project pages to the type the project removes.

2.  Update the extensions and flush the caches.

3.  Move the stored categories:

    ..  code-block:: bash

        vendor/bin/typo3 academic:projects:department:migrate

    With `academic_programs` installed, a `department` category moves when it
    is assigned to a project page and to no program page, and stays when it is
    assigned to program pages only. Hidden pages count, deleted pages do not,
    and pages of other types decide nothing; a translation or workspace version
    of a page counts by its own page type, which is normally the one of the
    page. A category assigned to program and project pages, or to no program or
    project page, keeps its type and is listed by uid and title: open it in the
    backend and set its type. Without `academic_programs` every `department`
    category moves.

    Translations and workspace versions of a category follow the category
    they belong to. A category in another language without an original in the
    default language is treated as a category of its own. Deleted categories
    stay as they are.

    The command can be run again. It moves only what is left, lists the
    remaining categories again and always finishes successfully.

4.  Replace `department` with `project_department` in the filter setting and in
    project templates. Search for `sys_category.projects.department` and
    `category_types.projects.department` as well.

5.  Flush the page cache.

To return to the previous version, restore its code and run:

..  code-block:: sql

    UPDATE sys_category SET type = 'department' WHERE type = 'project_department';

..  index:: Backend, Database, Frontend, TypoScript, NotScanned, ext:academic_projects
