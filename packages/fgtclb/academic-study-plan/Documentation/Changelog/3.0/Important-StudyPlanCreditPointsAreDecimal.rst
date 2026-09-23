..  _important-study-plan-credit-points-are-decimal:

=================================================
Important: Study plan credit points take decimals
=================================================

Description
===========

The credit points of a study plan semester and of a study plan module accept a
number with up to two decimals now, such as 2.5 for a module worth two and a
half credit points. The backend field used to accept whole numbers only. It
takes a decimal point or a decimal comma, and rounds a third decimal, as every
decimal field of the backend does.

The column :sql:`credit_points` of the tables
:sql:`tx_academicstudyplan_domain_model_semester` and
:sql:`tx_academicstudyplan_domain_model_module` changes from an integer to a
decimal column with two decimals. The extension no longer declares it in
:file:`ext_tables.sql`; TYPO3 derives it from the field definition. Version
2.4 carries the same change with a declared column, so an installation
updated from 2.4 already has the decimal column.

The study plan shows credit points without trailing zeros: "2.5" and "30",
never "2.50" or "30.00". The semesters and modules handed to the template carry
the credit points as a number rather than as the value the database returns,
so this holds for template overrides as well, see
:ref:`What a partial is given <templates-arguments>`.

Impact
======

Updating from 2.3 or older, the database compare offers a change of the
column in both tables. It keeps every stored value: a module with 5 credit
points keeps them, and the study plan shows "5" as before. Run it before
editors enter a decimal value, which the integer column cannot hold on
MariaDB, MySQL and PostgreSQL. Updating from 2.4, the compare leaves the
column alone on those, and on SQLite changes only its type.

On SQLite, updating from 2.x, the database analyzer can report two errors for
each of the two tables, "table … already exists" and "no such table:
__temp__…": 3.0 also adds the workspace columns to both tables, and on SQLite
both changes rebuild the table in the same run. The tables end up complete
with every value kept, and a second compare offers nothing more.
:bash:`extension:setup` does not report these errors.

A semester or module without credit points still shows none.

Credit points render with a decimal point on every page language. A template
override that wants a decimal comma formats the number itself, for example
with :html:`<f:format.number decimalSeparator=",">`, which prints a fixed
number of decimals.

Affected Installations
======================

Every installation with the extension installed. Run the database
compare in the install tool, or with :bash:`vendor/bin/typo3 extension:setup`,
after the update. From 2.3 or older, the change rewrites both tables; they hold
the semesters and modules of all study plans, which is rarely many rows.

An installation that redefined the column in an :file:`ext_tables.sql` of its
own - as a float column, for example - removes that definition before running
the compare. A declared column takes precedence over the derived one, so it
would otherwise stay as it is. A TCA override that only adds
:php:`'format' => 'decimal'` is no longer needed.

.. index:: Backend, Database, Frontend, TCA, ext:academic_study_plan
