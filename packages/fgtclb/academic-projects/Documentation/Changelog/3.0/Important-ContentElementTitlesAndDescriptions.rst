.. _important-content-element-titles-and-descriptions:

============================================================
Important: Content elements have new titles and descriptions
============================================================

Description
===========

The content elements of this extension have new titles and new descriptions, in
English and in German. They follow one wording that all academic extensions
share now. An editor sees the title in the new content element wizard and in
the type field of a content element, and the description below the title in the
wizard.

..  list-table::
    :header-rows: 1

    *   -   Content type
        -   Title until now
        -   Title now
        -   Description now
    *   -   :typoscript:`academicprojects_projectlist`
        -   :guilabel:`Projects` (German :guilabel:`Projekte`)
        -   :guilabel:`Projects` (German :guilabel:`Projekte`)
        -   Lists projects in a tile layout with sorting and filtering
    *   -   :typoscript:`academicprojects_projectlistsingle`
        -   :guilabel:`Projects (selected)` (German :guilabel:`Projekte (ausgewählte)`)
        -   :guilabel:`Selected Projects` (German :guilabel:`Projekte selektiert`)
        -   List of selected academic projects with sorting and filtering options

Impact
======

Editors see the new titles and descriptions. The content types, the label keys
and their files did not change. A site that replaces a title or a description,
with page TSconfig of the wizard, with
:typoscript:`TCEFORM.tt_content.CType.altLabels` or with a language file
override, keeps its own text.

Affected Installations
======================

Every installation that offers a content element of this extension to its
editors. Nothing has to be migrated.

.. index:: Backend, TSConfig, ext:academic_projects
