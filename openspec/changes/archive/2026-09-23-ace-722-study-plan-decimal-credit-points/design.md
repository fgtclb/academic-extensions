## Context

See `proposal.md` for the motivation. On this branch `ext_tables.sql:9` and
`:17` declare `credit_points int(11) NOT NULL DEFAULT '0'` on the semester and
the module table, the TCA uses `type => 'number'` with `range.lower => 0` and
no `format`, and `StudyPlanService` hands whole rows to the single template
`AcademicStudyPlan.html`. It prints the credit points in the column header,
the module and the module dialog inside
`<f:if condition="{….credit_points}">`, and the semester's value in the label
of the dialog trigger without one.

The backport analysis, per `docs/workflow/backporting.md`, against `main` with
the change applied and this branch at `43ca1aa74`:

| File                                               | Differs between the branches                                                      | Does the difference touch this change?                                                  |
|----------------------------------------------------|-----------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------|
| `ext_tables.sql`                                   | no                                                                                | yes: `main` removes the two lines, this branch changes them (see below)                 |
| `Configuration/TCA/…_semester.php`, `…_module.php` | yes: `main` declares `versioningWS`, has type icons and a v13-only `searchFields` | no - the `credit_points` column is identical, `format` goes in the same place           |
| `Classes/Service/StudyPlanService.php`             | yes: `main` overlays workspace versions (ACE-476)                                 | no - the cast goes after the language overlay, the last step here                       |
| `Resources/Private/Frontend/Default/Templates/…`   | yes: `main` split the template into partials (ACE-704)                            | no - the same four places print the value, three under a condition, the label without   |
| Tests                                              | yes: `main` has more rendering, localisation and workspace tests                  | the credit point tests are ported; the rendering assertions follow this branch's markup |

Checked against TYPO3 v12.4.45 and v13.4.35:

- `DefaultTcaSchema` of v12 derives no column for a TCA `number` field (it
  handles `category`, `datetime`, `slug`, `json` and `uuid`, and the relation
  types); v13 derives `DECIMAL(10,2)` only for a column `ext_tables.sql` does
  not declare. The column is therefore declared on this branch.
- `DataHandler::checkValueForNumber()` formats a `decimal` value to two
  decimals on both versions, accepting a decimal comma.
- `extension:setup` applies the `change` statements of the database compare
  on both versions (v12: `cms-extensionmanager`, v13: `cms-core`).
- Later definitions of a column overrule earlier ones in extension load order
  on both versions.

## Goals / Non-Goals

**Goals:**

- Exact storage, and output that looks as before for integer values.

**Non-Goals:**

- Summing credit points; the template shows what the editor entered.

## Decisions

### Declared decimal column

Both TCA columns get `format => 'decimal'`, and `ext_tables.sql` declares
`credit_points decimal(10,2) unsigned NOT NULL DEFAULT '0.00'` - the column
`main` derives on MariaDB, MySQL and PostgreSQL, so an update from 2.4 to 3.0
finds the same column there: `main`'s schema update test asserts that the
compare offers nothing for it on those three, and on SQLite only the rebuild
into the text column. On SQLite the declared column is `NUMERIC`, which
returns numbers rather than strings; `main` derives a text column on SQLite.

Rejected: a float column, which rounds in binary. Rejected: `varchar`, which
has no validation.

### Cast to a number in the service

As on `main`: `StudyPlanService` casts `credit_points` of every semester and
module row to `float` after the language overlay. PHP renders `2.5` for 2.50
and `30` for 30.00. The `<f:if>` conditions do not depend on it - Fluid reads
"0.00" as zero - but the label of the dialog trigger prints the semester's
credit points without a condition, and would announce "0.00 CP" without the
cast.

### Tests

- Rendering: the values the decimal column returns ("2.50", "30.00", "0.00")
  render as "2.5 CP", "30 CP" and no credit points, in the header, the module
  and its dialog; the label of the dialog trigger reads "30 CP" and "0 CP".
  SQLite returns numbers from the declared numeric column already, so these
  tell the cast apart on MariaDB, MySQL and PostgreSQL: without it they fail
  there with "30.00 CP" and "0.00 CP" (measured on v12 PostgreSQL).
- DataHandler: 2.5, 30, "2,5" and 2.555 on both tables store 2.5, 30, 2.5 and
  2.56, compared as numbers because SQLite returns a number.
- The service hands floats to the template.
- The database compare keeps an integer value stored in the former column,
  turns it into a decimal column, keeps the table's indexes and offers
  nothing further afterwards, on every DBMS of the matrix. It runs what
  `extension:setup` runs, every added and changed statement of the table; on
  SQLite that is a table rebuild with the indexes. Doctrine's own table
  rebuild, which the test uses to give the table the former column, loses the
  indexes on SQLite with DBAL 4 (TYPO3 v13), so the test puts them back
  first; DBAL 3 (TYPO3 v12) keeps them.

## Risks / Trade-offs

- [SQLite stores the declared `NUMERIC` column as an integer or a floating
  number] → The values keep their two decimals exactly at this size, and the
  service casts them anyway. The backend, however, compares the submitted
  "30.00" with the stored 30 as strings, so on SQLite a save of a semester or
  module whose credit points end in a zero (30.00, 2.50, 0.00) writes them
  again and adds a history entry. That affects SQLite installations only,
  the development instances among them. `main` avoids it with the text column
  v13 and v14 derive on SQLite; a column declared in `ext_tables.sql` cannot
  differ per platform.
- [A schema change in a minor release] → Data preserving, and applied by the
  database compare every update runs; the `Important` entry says so.
- [The ALTER on a large table] → The tables are small (semesters and modules
  of one element each).
- [Output uses a decimal point] → A template override can format the float
  per locale.

## Migration Plan

Update, run the database compare, done; nothing is migrated by code.
Projects that redefined the columns remove their `ext_tables.sql` definition
before the compare.

## Open Questions

None.
