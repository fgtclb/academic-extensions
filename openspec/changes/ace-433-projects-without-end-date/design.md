## Context

Measured on `2` `e4c581602`, TYPO3 12.4.45 and 13.4.36 (`.Build/vendor` after
`composerUpdate` for each).

- `packages/fgtclb/academic-projects/ext_tables.sql:6` declares
  `tx_academicprojects_end_date int(11) DEFAULT NULL`. The TCA field
  (`Configuration/TCA/Overrides/pages.php:98-108`) is `datetime` with
  `format: date`, without `nullable` and without `default`.
- `ProjectRepository::findByDemand()` filters "Active" as
  `end_date = 0 OR end_date > now` and "Completed" as
  `end_date > 0 AND end_date < now`. A `NULL` matches neither.
- How the column stays `NULL`, the same on both cores:
  `DataHandler::newFieldArray()` (v12 `:8362`, v13 `:8239`) presets only fields
  with a TCA `default` or a `TCAdefaults` entry in TSconfig, and `compareFieldArrayWithCurrentAndUnset()` (v12
  `:8467`, v13 `:8326`) compares a field that is not `nullable` as integers
  through `isSubmittedValueEqualToStoredValue()`, so an empty input equals the
  stored `NULL` and the field is dropped as unchanged.
- `equals($property, null)` renders `IS NULL` in the Extbase query parser on
  both cores: `Typo3DbQueryParser::parseDynamicOperand()` takes the operand
  unconverted (v12 `:405-407`, v13 `:398-400`) and `OPERATOR_EQUAL_TO` renders
  `IS NULL` for `null` (v12 `:430-432`, v13 `:425-427`).
- The gap is pinned by `Tests/Functional/Domain/Repository/ProjectRepositoryTest.php`:
  `theActiveStateSelectsOpenEndedAndStillRunningProjects()` expects `[10, 11]`,
  `aProjectWithoutAnyEndDateValueIsNeitherActiveNorCompleted()` leaves out
  uid 13, the fixture row with `\NULL`. Its docblock claims that FormEngine
  writes `0`, which the core does not do for an existing `NULL`.
- Different from `main`: this branch has no project state on the model, no
  badge and no manual paragraph about the gap. The filter is the only place
  that decides whether a project is active.

## Goals / Non-Goals

**Goals:**

- The "Active" filter lists a project without a stored end date on v12 and v13.
- A query change only, without an upgrade step.

**Non-Goals:**

- Normalising stored values. A `NULL` and a `0` keep meaning the same.
- Porting the project state or the badge of 3.0.

## Decisions

### `NULL` joins the "Active" condition of the query

The "Active" `logicalOr()` gets a third arm,
`$query->equals('txAcademicprojectsEndDate', null)`, with the same comment as
on `main`. "Completed" stays as it is, its `> 0` already keeps `NULL` out.
No version switch is needed, both cores render the arm the same way.

Rejected, as on `main`: a `NOT NULL DEFAULT 0` column, because the schema
comparison fails on PostgreSQL and strict MySQL while `NULL` rows exist, so
every installation would have to rewrite those rows before its schema update.
A TCA `default => 0`, because it leaves
existing rows and imports alone. A one-off wizard, because the column default
keeps producing new `NULL` rows.

### The requirement goes into `academic-projects/list-filter`

`main` modified its capability `project-active-state`, which describes the
state on the model and does not exist here. The filter behaviour is what a
visitor observes on this branch, so it is an added requirement of the
existing list filter capability.

### Tests

The repository tests are turned around as on `main`. The plugin test asserts
the rendered titles of a content element preset to active projects, since the
cards on this branch carry no state badge to read.

## Risks / Trade-offs

- [`NULL` handling differs between database systems] → The repository and the
  plugin tests run on SQLite, PostgreSQL and MariaDB for v12 and v13.
- [Paginated "Active" lists change] → An `Important` entry for 2.4 names the
  effect.

## Migration Plan

None. The fix is effective on deployment, the list action is not cached.

## Open Questions

None.
