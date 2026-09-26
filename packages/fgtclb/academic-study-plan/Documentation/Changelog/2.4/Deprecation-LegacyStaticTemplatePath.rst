..  _deprecation-legacy-static-template-path:

====================================================
Deprecation: The static template path of version 2.3
====================================================

Description
===========

Up to version 2.3 this extension offered one static template, stored in a
:sql:`sys_template` record as
`EXT:academic_study_plan/Configuration/TypoScript/Default`. Version 2.4 moves
the TypoScript into component folders, see
:ref:`breaking-site-sets-and-static-templates-restructured`, and leaves no
TypoScript of its own in the folder of that path.

The path keeps delivering. Its :file:`constants.typoscript` and
:file:`setup.typoscript` import the files of the same name in
:file:`Configuration/TypoScript/ContentElement/`, so a record that stores the
path gets what :guilabel:`Academic Study Plan: All components
(academic_study_plan)` delivers, and an import of the two files gets what an
import of the files in :file:`Configuration/TypoScript/ContentElement/` gets.

In version 2.3 the folder also added the stylesheet and the script of the
content element to every page. The content element template registers them
itself now, switchable with
:typoscript:`plugin.tx_academicstudyplan.assets.css` and
:typoscript:`plugin.tx_academicstudyplan.assets.js`, so the path does not add
them to the page.

The path is offered in the static template list again, as :guilabel:`Academic
Study Plan: Path up to 2.3 (deprecated, use All components)
(academic_study_plan)`, so saving the record keeps it. It is deprecated and
will be removed in version 4.0.

..  important::

    Version 2.3 had no :file:`constants.typoscript` in this folder, and its
    :file:`setup.typoscript` needed none. The setup reads constants now. A site
    package that imports only
    :file:`EXT:academic_study_plan/Configuration/TypoScript/Default/setup.typoscript`
    has to import
    :file:`EXT:academic_study_plan/Configuration/TypoScript/Default/constants.typoscript`
    into its constants as well — otherwise the template paths stay unresolved,
    and the content element cannot find its template.

Impact
======

An installation that selects :guilabel:`All components` or depends on the site
set notices nothing. An installation that still stores the path of version 2.3,
or imports its files, keeps its content element configuration.

A site that depends on the site set and still stores the path of version 2.3 in
its :sql:`sys_template` record, or imports its files, reads the TypoScript
twice, as it did in version 2.3. See :ref:`one-mechanism-per-site` for what
that costs. From version 3.0 on, the command :bash:`academic:upgrade:check`
reports a site that combines the site set with a static template of the same
extension.

Affected Installations
======================

Installations that store
`EXT:academic_study_plan/Configuration/TypoScript/Default` in a
:sql:`sys_template` record, or import
:file:`EXT:academic_study_plan/Configuration/TypoScript/Default/setup.typoscript`
in a site package.

Migration
=========

Select :guilabel:`Academic Study Plan: All components (academic_study_plan)` in
the :sql:`sys_template` record instead of the path up to 2.3, or depend on the
set :yaml:`fgtclb/academic-study-plan` in the site configuration.

Replace an import of the old files with the files in
`EXT:academic_study_plan/Configuration/TypoScript/ContentElement/`, or remove
it when the site depends on the site set.

..  index:: TypoScript, ext:academic_study_plan
