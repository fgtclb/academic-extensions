.. _feature-ace-250-academic-contacts4pages:

==================================================================
Feature: "Show hidden records" plugin option for the contacts list
==================================================================

Description
===========

A new boolean plugin option **Show hidden records**
(:typoscript:`settings.showHiddenRecords`, checkbox/toggle, default off)
was added to the following plugin:

* **Contacts for this page** (:php:`academiccontacts4pages_list`)

This plugin previously had no flexform at all. A new
:file:`Configuration/FlexForms/ContactsList.xml` data structure is
introduced and registered for the plugin (a "Configuration" tab with the
:sql:`pi_flexform` field is added to the content element), so the option
can be configured per plugin instance.

When the option is enabled, the frontend contacts listing includes hidden
(disabled) records, independent of the Context API visibility settings.
Only the `hidden` enable column (`disabled`) is ignored; the `deleted`
restriction stays in effect.

Impact
======

Editors can now opt in per plugin instance to display hidden contacts in
the frontend, for example to preview intentionally hidden records without
changing the global preview settings. The option is off by default, so
existing plugin instances keep their current behaviour.

Affected Installations
======================

All installations using the `EXT:academic_contacts4pages` extension
starting with version 2.4. No action is required for existing
installations.

.. index:: Backend, Frontend, ext:academic_contacts4pages
