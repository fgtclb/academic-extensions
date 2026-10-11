.. _feature-ace-250-academic-contacts4pages:

==================================================================
Feature: "Show hidden records" plugin option for the contacts list
==================================================================

Description
===========

A new boolean plugin option **Show hidden records**
(:typoscript:`settings.showHiddenRecords`, a toggle, off by default) was added
to the following plugin:

* **Contacts for this page** (:php:`academiccontacts4pages_list`)

This plugin previously had no flexform at all. The new data structure
:file:`Configuration/FlexForms/ContactsList.xml` is registered for it, and the
content element gains a :guilabel:`Configuration` tab with the
:sql:`pi_flexform` field, so the option can be configured per plugin instance.

When the option is enabled, the frontend contacts listing includes hidden
(disabled) records, also for visitors without a preview of hidden records. Only
the `hidden` enable column (`disabled`) is ignored. Deleted records stay
excluded.

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
