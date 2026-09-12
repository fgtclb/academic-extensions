## Context

See `proposal.md` for the motivation. On main `ext_tables.sql:9` and `:17`
declare `credit_points int(11) NOT NULL DEFAULT '0'` on the semester and the
module table. The TCA uses `type => 'number'` with `range.lower => 0` and no
`format` (`tx_academicstudyplan_domain_model_semester.php`,
`tx_academicstudyplan_domain_model_module.php`). `StudyPlanService` selects
whole rows with `select('*')` and hands them to the template, which prints
`{semester.credit_points}` and `{module.credit_points}` as they come.

`DefaultTcaSchema` derives a `DECIMAL` column for `type => 'number'` with
`format => 'decimal'` on both versions (v13.4.35 `:888`, v14.3.6 `:880`), but
only for a column that `ext_tables.sql` does not declare.

## Goals / Non-Goals

**Goals:**

- Exact storage, and output that looks as before for integer values.

**Non-Goals:**

- Summing credit points; the template shows what the editor entered.

## Decisions

### Decimal format, derived column

Both TCA columns get `format => 'decimal'`, and the two `credit_points` lines
are removed from `ext_tables.sql`, so the core derives the decimal column
(precision 10, scale 2). The database compare changes the column type in
place, which keeps integer values.

Rejected: a float column (ace-demo, one project), which rounds in binary.
Rejected:
`varchar`, which has no validation. Rejected: a separate "half point" flag,
two fields for one number.

### Cast to a number in the service

`StudyPlanService` casts `credit_points` of every semester and module row to
`float` after the workspace and language overlay. PHP renders `2.5` for 2.50
and `30` for 30.00, so the default template and every override that prints
the value show no trailing zeros without a template change.

Rejected: formatting in the template with `f:format.number`, which renders a
fixed number of decimals (`30.00`) and would have to be repeated in every
override.

### Decided: `main` only, no backport to branch `2`

The change ships with 3.0 on `main` and is not backported. The premise that
three projects on branch `2` use the study plan does not hold: one of them
runs `main`, one runs the unmaintained `2.2` line and already overrides the
column as a decimal field, and one runs a fork of its own under the same
package name, so no project on `2` would consume the backport.

### Decided: no locale formatting in this change

The float cast stays, and credit points render with a decimal point ("2.5")
on every page language; formatting per locale stays a template concern. No
analysed project formats the value today, all print their float columns
raw. Formatting in the service would change the variable's type to a string
for every override that computes with it. A locale-aware formatting
ViewHelper in the default template, keeping the float in the data, can follow
in a change of its own when a project asks, without touching storage or the
service.

## Risks / Trade-offs

- [SQLite stores `NUMERIC` as a floating number] → The core notes the rounding
  issue for SQLite next to the decimal derivation; the functional tests run on
  SQLite and PostgreSQL and assert the stored and rendered value.
- [The ALTER on a large table] → The tables are small (semesters and modules
  of one element each); the changelog still asks for the compare in a
  maintenance window on MySQL.
- [Output uses a decimal point] → German pages show "2.5"; a template
  override can format the float per locale, and a formatting ViewHelper can
  follow (see the decision above).

## Migration Plan

Update, run the database compare, done; nothing is migrated by code.
Projects that redefined the columns as float remove their `ext_tables.sql`
and TCA overrides before the compare.

## Open Questions

None.
