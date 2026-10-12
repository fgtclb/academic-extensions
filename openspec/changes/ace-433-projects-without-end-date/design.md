## Context

Measured on `main` `e75f86b95`, TYPO3 13.4.35 (`core-13/vendor`) and 14.3.7
(`.Build/vendor`).

- `packages/fgtclb/academic-projects/ext_tables.sql:6` declares
  `tx_academicprojects_end_date int(11) DEFAULT NULL`. The TCA field
  (`Configuration/TCA/Overrides/pages.php:104-114`) is `datetime` with
  `format: date`, without `nullable` and without `default`.
- `ProjectRepository::findByDemand()` (`Classes/Domain/Repository/ProjectRepository.php:64-85`)
  filters "Active" as `end_date = 0 OR end_date > now` and "Completed" as
  `end_date > 0 AND end_date < now`. A `NULL` matches neither, because both
  comparisons are unknown for `NULL`.
- `ActiveState::fromEndDate()` (`Classes/Domain/Model/Dto/ActiveState.php:35-41`,
  ACE-796) treats a missing end date as active, and `Project::getActiveState()`
  uses it. The card says "Active" while the filter drops the project.
- How the column stays `NULL`, read in both cores: `DataHandler::newFieldArray()`
  (v14 `DataHandler.php:8240`, v13 `:8237`) presets only fields with a TCA
  `default`, so a page created with a datamap that does not carry the field
  (page tree, doktype switch, import) stores `NULL`.
  `compareFieldArrayWithCurrentAndUnset()` (v14 `:8380`, v13 `:8324`) compares
  the submitted empty value with the stored one as integers for a non nullable
  field, finds them equal and drops the field, so a later backend save keeps
  `NULL`.
- The gap is pinned on purpose by ACE-796:
  `Tests/Functional/Domain/Repository/ProjectRepositoryTest.php`
  `theActiveStateSelectsOpenEndedAndStillRunningProjects()` expects `[10, 11]`,
  `aProjectWithoutAnyEndDateValueIsNeitherActiveNorCompleted()` (`:107`) and the
  `active` case of `filteredStates()` (`:145-149`) leave out uid 13, the fixture
  row with `\NULL` in `Fixtures/ProjectRepository/projectsWithEndDates.csv`.
  The docblocks at `:98-104` and `:151-156` name ACE-433.
- The gap is documented: `Documentation/Configuration/Index.rst:541-545` and
  `Documentation/Changelog/3.0/Feature-ProjectActiveStateBadge.rst:38-40`.
- `equals($property, null)` renders `IS NULL` in the Extbase query parser
  (`Typo3DbQueryParser.php`, v14 `:403-404`, v13 `:426-427`). The value is not
  converted through the date mapping before that check, on both cores.

## Goals / Non-Goals

**Goals:**

- The "Active" filter and the model state follow one rule, including `NULL`.
- The change is a query change only and needs no upgrade step.

**Non-Goals:**

- Normalising stored values. A `NULL` and a `0` keep meaning the same.

## Decisions

### `NULL` joins the "Active" condition of the query

The "Active" `logicalOr()` gets a third arm,
`$query->equals('txAcademicprojectsEndDate', null)`. The "Completed" condition
stays as it is, its `> 0` already keeps `NULL` out.

This follows `ActiveState::fromEndDate()`, where no end date is active, and the
open ended rule the filter already has for `0`. It also answers the issue's own
alternative, "treat `NULL` as one of the two states explicitly".

Rejected: a `NOT NULL DEFAULT 0` column. The schema comparison would turn the
existing `NULL` rows into a failing `ALTER`: PostgreSQL refuses `SET NOT NULL`
while `NULL` rows exist, and strict MySQL refuses the `MODIFY`. Upgrade wizards
run after the comparison, so the rows would need rewriting by a DataHandler
hook or a manual SQL step before it. That is out of proportion for a filter
defect, and imports that write the column directly could still write `NULL`
on databases that were not migrated.

Rejected: a TCA `default => 0` on the field. It only affects records created
from now on through DataHandler, leaves every existing `NULL` row where it is,
and does nothing for imports.

Rejected: an upgrade wizard that writes `0` into every `NULL` end date. It
fixes the data once, but the column default keeps producing new `NULL` rows,
so the query would still need the arm.

### The tests that pin the gap are turned around, not removed

`theActiveStateSelectsOpenEndedAndStillRunningProjects()` expects
`[10, 11, 13]`. `aProjectWithoutAnyEndDateValueIsNeitherActiveNorCompleted()`
becomes a test that uid 13 is in the "Active" result and not in the
"Completed" one, renamed to say so. The `active` case of `filteredStates()`
gains `13 => 'active'`, which makes `everyProjectAFilterListsIsInTheStateOfThatFilter()`
the proof that the model and the filter agree on `NULL`. The docblocks that
announce ACE-433 describe the fixed behaviour instead.

## Risks / Trade-offs

- [`NULL` handling is where the database systems differ] → The repository
  tests run on SQLite, PostgreSQL and MariaDB for v13 and v14.
- [Paginated "Active" lists change] → An `Important` changelog entry names the
  effect. The change adds entries, it removes none.
- [A third party listener or subclass relies on the old result] → The query is
  not an extension point. The entry states the new result, which is what a
  visitor expected from the filter.

## Migration Plan

None. The fix is effective on deployment, without a cache flush beyond the
usual one for a code update, since the list action is not cached.

## Open Questions

None.
