..  _breaking-start-date-sorting-constant-renamed:

=======================================================
Breaking: The descending start date constant is renamed
=======================================================

Description
===========

The constant
:php:`\FGTCLB\AcademicProjects\Enumeration\SortingOptions::SORT_BY_STARTDATE_desc`
is renamed to :php:`SortingOptions::SORT_BY_STARTDATE_DESC`, the spelling of
every other sorting option. Its value,
:php:`'tx_academicprojects_start_date desc'`, is unchanged.

No alias constant is kept for the old name.
:php:`SortingOptions::getConstants()` lists every constant of the class, so an
alias would offer the descending start date twice, in the sorting select of the
plugin and in the sorting select of the visitor.

Impact
======

PHP code that names :php:`SortingOptions::SORT_BY_STARTDATE_desc` fails with an
error.

Stored values are not affected. The FlexForm of the plugin, the sorting select
of the visitor and the route enhancer of the list work with the value of the
constant, never with its name, so a project list that sorts by start date
descending keeps doing so without a migration, on TYPO3 v13 and v14.

Affected Installations
======================

Installations whose own code names the constant. Search the site package for
it:

..  code-block:: bash

    grep -rn "SORT_BY_STARTDATE_desc" packages/my_sitepackage/

Migration
=========

Use :php:`SortingOptions::SORT_BY_STARTDATE_DESC` instead.

..  index:: PHP-API, ext:academic_projects
