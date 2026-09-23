## Context

See `proposal.md` for the motivation. On main `ext_tables.sql:9` and `:17`
declare `credit_points int(11) NOT NULL DEFAULT '0'` on the semester and the
module table. The TCA uses `type => 'number'` with `range.lower => 0` and no
`format` (`tx_academicstudyplan_domain_model_semester.php`,
`tx_academicstudyplan_domain_model_module.php`). `StudyPlanService` selects
whole rows with `select('*')` and hands them to the template, whose partials
`StudyPlan/Semester`, `StudyPlan/Module` and `StudyPlan/ModuleDialog` print
`{semester.credit_points}` and `{module.credit_points}` as they come: in the
column header, the module and its dialog inside
`<f:if condition="{….credit_points}">`, and the semester's value in the label
of the dialog trigger without one.

`DefaultTcaSchema` derives a `DECIMAL` column for `type => 'number'` with
`format => 'decimal'` on both versions (v13.4.35 `:888`, v14.3.6 `:880`), but
only for a column that `ext_tables.sql` does not declare. On SQLite it
derives `VARCHAR(255) DEFAULT '0.00'` instead, so every DBMS returns the
value as a string with two decimals.

## Goals / Non-Goals

**Goals:**

- Exact storage, and output that looks as before for integer values.

**Non-Goals:**

- Summing credit points; the template shows what the editor entered.

## Decisions

### Decimal format, derived column

Both TCA columns get `format => 'decimal'`, and the two `credit_points` lines
are removed from `ext_tables.sql`, so the core derives the decimal column
(precision 10, scale 2). The database compare changes the column type, which
keeps integer values; on SQLite it rebuilds the table, indexes included.

Rejected: a float column (ace-demo, one project), which rounds in binary.
Rejected: `varchar`, which has no validation. Rejected: a separate "half
point" flag, two fields for one number.

### Cast to a number in the service

`StudyPlanService` casts `credit_points` of every semester and module row to
`float` after the workspace and language overlay. PHP renders `2.5` for 2.50
and `30` for 30.00, so the default template and every override that prints
the value show no trailing zeros without a template change.

The conditions in the partials do not depend on the cast: Fluid reads a
numeric string as a number, so `<f:if>` is false for "0.00" as it was for
0. What does depend on it is the label the dialog trigger announces, which
prints the semester's credit points without a condition: without the cast
a semester without credit points would be announced as "0.00 CP" there.

Rejected: formatting in the template with `f:format.number`, which renders a
fixed number of decimals (`30.00`) and would have to be repeated in every
override.

### Decided: backported to branch `2`

The maintainer decided on 2026-09-23 to backport the change to branch `2`
(2.4), against the recommendation of the project analysis, which had found
that no analysed project on `2` would consume it: one of the three projects runs
`main`, one runs the unmaintained `2.2` line and already overrides the column,
and one runs a fork of its own under the same package name. The backport is a
change of its own on branch `2`, re-derived from a backport analysis there
(TYPO3 v12 and v13).

### Decided: no locale formatting in this change

The float cast stays, and credit points render with a decimal point ("2.5")
on every page language; formatting per locale stays a template concern. No
analysed project formats the value today, all print their float columns
raw. Formatting in the service would change the variable's type to a string
for every override that computes with it. A locale-aware formatting
ViewHelper in the default template, keeping the float in the data, can follow
in a change of its own when a project asks, without touching storage or the
service.

### Tests

- The rendering test stores the values as the decimal column returns them
  ("2.50", "30.00", "0.00") and asserts the visible text of the whole plan,
  including the dialog and the label the dialog trigger announces ("0 CP" for
  a semester without credit points, the one place a zero is printed), and
  that a zero shows no credit points elsewhere.
- A DataHandler test writes 2.5, 30, "2,5" and 2.555 to both tables and reads
  back 2.50, 30.00, 2.50 and 2.56.
- The service test asserts the floats handed to the template.
- The existing-values requirement is tested rather than checked by hand in the
  development instances, which run SQLite only. The test gives the table the
  shape 2.x declared - the former integer column and no workspace columns -
  stores 5, runs what the database compare offers the way `extension:setup`
  does (added and changed statements, once), and reads 5 back and 2.50 after
  writing a decimal. A second test starts from the column branch `2`
  declares: the compare leaves it alone on MariaDB, MySQL and PostgreSQL, and
  rebuilds the table on SQLite. Both assert that the table then has every
  column and index the definition declares and that the compare offers
  nothing further. They run on every DBMS of the matrix.
- On SQLite the workspace columns and the column change each rebuild the
  table in that one run, and the run reports two errors of the second rebuild
  although the table ends up complete: the statements both rebuilds share run
  once (measured on v13 and v14; a second run offers nothing). The test
  asserts those two errors on SQLite and none elsewhere, and the changelog
  names them. Doctrine's own table rebuild, which the test uses to
  give the table its former shape, loses the secondary indexes on SQLite, so
  the test puts them back first.

## Risks / Trade-offs

- [SQLite stores `NUMERIC` as a floating number] → The core derives a text
  column on SQLite instead, which keeps "2.50" as written; a value copied from
  the former integer column stays "5" there, which the cast renders the same.
- [The ALTER on a large table] → The tables are small (semesters and modules
  of one element each); the changelog says the change rewrites both tables.
- [Output uses a decimal point] → German pages show "2.5"; a template
  override can format the float per locale, and a formatting ViewHelper can
  follow (see the decision above).

## Migration Plan

Update, run the database compare, done; nothing is migrated by code.
Projects that redefined the columns as float remove their `ext_tables.sql`
and TCA overrides before the compare.

## Open Questions

None.
