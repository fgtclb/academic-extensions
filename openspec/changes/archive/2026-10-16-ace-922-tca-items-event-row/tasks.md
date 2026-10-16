## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicJobsNewJobFormSelectItemsListenerTest` of `academic_jobs`
  with the new fixture extension `test_job_select_items_listener`, whose
  listener reads `row` without a fallback and adds a choice for the page of
  the record. Run against the code of `main`, red on TYPO3 v13 and then v14
  with the warning `Undefined array key "row"` and a `TypeError`
  (`.agent/tmp/ace-922/logs/red-13-selectitems.log`, `red-14-selectitems.log`).
- [x] 1.2 `GetSelectItemsForTcaManagedTableFieldMethodTraitTest` of
  `academic_base` pins the keys the helper passes to an `itemsProcFunc`, with
  `row` as an empty array. Red on v13 and v14 against `main` by the missing
  key (`red-13-trait.log`, `red-14-trait.log`).

## 2. Implementation

- [x] 2.1 The helper trait passes `'row' => []` after `table`, verified by 1.1
  and 1.2 green on v13 and v14.
- [x] 2.2 `ModifyTcaSelectFieldItemsEvent`, `TypeItems` and
  `EmploymentTypeItems` declare `row?: array<string, mixed>` in every shape,
  verified by PHPStan reporting the unguarded read of the fixture listener on
  both cores, which the fixture then ignores inline with its reason.
- [x] 2.3 Both PHPStan baselines checked for entries about the event, the
  trait and the providers: none exist, nothing to remove or add.

## 3. Documentation

- [x] 3.1 The event chapter of the `academic_base` manual and the one of the
  `academic_jobs` manual say that `row` is optional, empty in the job form,
  and how to read it and its fields with a fallback.
- [x] 3.2 `academic_base` gets the 2.4 entry
  `Important-SelectItemHelperPassesAnEmptyRow.rst`, true on TYPO3 v12 and v13
  and with references that resolve on branch `2`, and the section on own
  select handlers of `Upgrade/From23To24.rst` and `Upgrade/From23To30.rst`
  names the fallback.
- [x] 3.3 `docs/architecture/backend-select-items.md` gets a section on the
  parameters outside FormEngine, `docs/testing/fixture-extensions.md` lists
  the new fixture and its counts.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_base` and
  `academic_jobs` manuals, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-922`, and
  the archive of this change with the spec sync as the last commit.
