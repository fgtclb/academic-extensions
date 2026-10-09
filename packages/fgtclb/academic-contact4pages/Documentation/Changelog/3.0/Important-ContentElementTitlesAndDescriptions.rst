.. _important-content-element-titles-and-descriptions:

============================================================
Important: Content elements have new titles and descriptions
============================================================

Description
===========

The content element of this extension has a new title and a new description, in
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
    *   -   :typoscript:`academiccontacts4pages_list`
        -   :guilabel:`Contacts for this page` (German :guilabel:`Kontakte für diese Seite`)
        -   :guilabel:`Person’s Contacts on the Page` (German :guilabel:`Personen Kontakte der Seite`)
        -   List of contacts linked to a page

Impact
======

Editors see the new title and description. The content type, the label keys
and their files did not change. A site that replaces a title or a description,
with page TSconfig of the wizard, with
:typoscript:`TCEFORM.tt_content.CType.altLabels` or with a language file
override, keeps its own text.

Affected Installations
======================

Every installation that offers a content element of this extension to its
editors. Nothing has to be migrated.

.. index:: Backend, TSConfig, ext:academic_contacts4pages
