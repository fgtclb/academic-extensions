..  _breaking-plugins-require-ctype-on-typo3-v14:

======================================================
Breaking: Extbase plugins require `CType` on TYPO3 v14
======================================================

Description
===========

TYPO3 v14 removed the `tt_content` sub-type feature (the `list_type` column) and
changed `ExtensionManagementUtility::addPlugin()` accordingly. The academic
plugins have been registered as first-class content elements (`CType`) since the
`2.0` version line (see the `2.0` breaking note about migrating from `list_type`
to `CType`); for TYPO3 v14 support the internal registration was adapted to the
new `addPlugin()` signature and the vestigial `list_type` handling was dropped.

In addition the "New Content Element" wizard TSconfig still created the plugins
as `CType=list` with a `list_type`, contradicting the `CType` registration; it
now creates them with the dedicated `CType` directly.

Impact
======

On TYPO3 v14 the `tt_content.list_type` column no longer exists. Any content
records still stored as `CType=list` with a `list_type` of one of the plugins
below will no longer resolve, and custom TypoScript, TSconfig, page TSconfig or
SQL that references `list_type` for these plugins stops working.

The change relates to the following plugins:

* `academicprojects_projectlist`
* `academicprojects_projectlistsingle`

Affected Installations
======================

Installations that upgrade to TYPO3 v14 and still hold content elements stored
as `CType=list` + `list_type=<plugin>` (including elements created through the
previous "New Content Element" wizard), or that reference `list_type` for these
plugins in their own configuration.

Migration
=========

Run the provided upgrade wizard `academicProjects_pluginUpgradeWizard` **before**
upgrading to TYPO3 v14 (it requires the `list_type` column, which v14 removes) to
migrate the `tt_content` records to the dedicated `CType` values. Update any
custom configuration referencing `list_type` to match on `CType` instead.

.. index:: Database, TCA, ext_localconf, TSConfig
