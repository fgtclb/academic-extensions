..  _important-ace-722-academic-study-plan-derived-column:

============================================================
Important: The credit point columns are derived from the TCA
============================================================

Description
===========

Version 2.4 changed the column :sql:`credit_points` of the tables
:sql:`tx_academicstudyplan_domain_model_semester` and
:sql:`tx_academicstudyplan_domain_model_module` to a decimal column with two
decimals, see :ref:`important-study-plan-credit-points-are-decimal`, and
declared it in :file:`ext_tables.sql`. This version no longer declares the
column. TYPO3 derives it from the field definition, a decimal column with two
decimals as well.

Impact
======

Updating from 2.4, the database compare leaves the column alone on MariaDB,
MySQL and PostgreSQL, and on SQLite changes only its type.

On SQLite the database analyzer can report two errors for each of the two
tables, "table … already exists" and "no such table: __temp__…". This version
also adds the workspace columns to both tables, and on SQLite both changes
rebuild the table in the same run. The tables end up complete with every value
kept, and a second compare offers nothing more. :bash:`extension:setup` does
not report these errors.

Affected Installations
======================

Installations on SQLite, and installations that redefined the column in an
:file:`ext_tables.sql` of their own. A declared column takes precedence over the
derived one, so such a definition keeps the column as it is. Remove it before
running the compare.

.. index:: Database, TCA, ext:academic_study_plan
