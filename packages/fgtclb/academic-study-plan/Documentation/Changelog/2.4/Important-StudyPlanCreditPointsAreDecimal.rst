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
:sql:`tx_academicstudyplan_domain_model_module` changes from
:sql:`int(11)` to :sql:`decimal(10,2)`.

The study plan shows credit points without trailing zeros: "2.5" and "30",
never "2.50" or "30.00". The semesters and modules handed to the template carry
the credit points as a number rather than as the value the database returns,
so this holds for template overrides as well: printed as they are, they read
"2.5", "30" and "0".

Impact
======

The database compare offers a change of the column in both tables. It keeps
every stored value: a module with 5 credit points keeps them, and the study
plan shows "5" as before. Run it before editors enter a decimal value, which
the integer column cannot hold on MariaDB, MySQL and PostgreSQL.

A semester or module without credit points still shows none.

Credit points render with a decimal point on every page language. A template
override that wants a decimal comma formats the number itself, for example
with :html:`<f:format.number decimalSeparator=",">`, which prints a fixed
number of decimals.

Affected Installations
======================

Every installation with the extension installed. Run the database
compare in the install tool, or with :bash:`vendor/bin/typo3 extension:setup`,
after the update. The change rewrites both tables; they hold the semesters and
modules of all study plans, which is rarely many rows.

An installation that redefined the column in an :file:`ext_tables.sql` of its
own - as a float column, for example - removes that definition before running
the compare. A definition of an extension loaded later, such as a site package
that depends on this one, takes precedence, so the column would otherwise stay
as it is. A TCA override that only adds :php:`'format' => 'decimal'` is no
longer needed.

.. index:: Backend, Database, Frontend, TCA, ext:academic_study_plan
