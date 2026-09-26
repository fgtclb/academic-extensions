..  _deprecation-legacy-static-template-path:

====================================================
Deprecation: The static template path of version 2.3
====================================================

Description
===========

Up to version 2.3 this extension offered one static template, stored in a
:sql:`sys_template` record as
`EXT:academic_contacts4pages/Configuration/TypoScript/`. Version 2.4 moves the
TypoScript into component folders, see
:ref:`breaking-site-sets-and-static-templates-restructured`, and leaves no
TypoScript of its own in the folder of that path.

The path keeps delivering. Its :file:`constants.typoscript` and
:file:`setup.typoscript` import the files of the same name in
:file:`Configuration/TypoScript/List/`, so a record that stores the path gets
the TypoScript of this extension that :guilabel:`Academic Contacts4Pages: All
components (academic_contacts4pages)` delivers, and an import of the two files
gets what an import of the files in :file:`Configuration/TypoScript/List/`
gets.

Unlike :guilabel:`Academic Contacts4Pages: All components
(academic_contacts4pages)`, the path does not bring the TypoScript of
EXT:academic_persons along. A record of version 2.3 stores the entry of that
extension next to the path, and keeps reading it from there, once.

The path is offered in the static template list again, as :guilabel:`Academic
Contacts4Pages: Path up to 2.3 (deprecated, use All components)
(academic_contacts4pages)`, so saving the record keeps it. It is deprecated and
will be removed in version 4.0.

..  important::

    Version 2.3 had no :file:`constants.typoscript` in this folder, and its
    :file:`setup.typoscript` needed none. The setup reads constants now. A site
    package that imports only
    :file:`EXT:academic_contacts4pages/Configuration/TypoScript/setup.typoscript`
    has to import
    :file:`EXT:academic_contacts4pages/Configuration/TypoScript/constants.typoscript`
    into its constants as well — otherwise the view paths and the settings that
    read constants keep their unresolved :typoscript:`{$…}` value.

Impact
======

An installation that selects :guilabel:`All components` or depends on the site
set notices nothing. An installation that still stores the path of version 2.3,
or imports its files, keeps its plugin configuration.

A site that depends on the site set and still stores the path of version 2.3 in
its :sql:`sys_template` record, or imports its files, reads the TypoScript
twice, as it did in version 2.3. See :ref:`one-mechanism-per-site` for what
that costs. From version 3.0 on, the command :bash:`academic:upgrade:check`
reports a site that combines the site set with a static template of the same
extension.

Affected Installations
======================

Installations that store
`EXT:academic_contacts4pages/Configuration/TypoScript/` in a
:sql:`sys_template` record, or import
:file:`EXT:academic_contacts4pages/Configuration/TypoScript/setup.typoscript`
in a site package.

Migration
=========

Select :guilabel:`Academic Contacts4Pages: All components
(academic_contacts4pages)` in the :sql:`sys_template` record instead of the
path up to 2.3, or depend on the set :yaml:`fgtclb/academic-contacts4pages` in
the site configuration.

:guilabel:`All components` brings the TypoScript of EXT:academic_persons along,
for the plugins of that extension as well. Remove the separate entry
:guilabel:`Academic Persons: Shared plugin settings (academic_persons)` —
called :guilabel:`Academic Persons Settings (academic_persons)` in version 2.3
and stored as `EXT:academic_persons/Configuration/TypoScript/Default` — from
the record when you select :guilabel:`All components`, or that TypoScript is
read twice.

Replace an import of the old files with the files in
`EXT:academic_contacts4pages/Configuration/TypoScript/List/`, or remove it when
the site depends on the site set.

..  index:: TypoScript, ext:academic_contacts4pages
