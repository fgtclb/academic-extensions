## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicPersonsSelectionOutsideStartingpointTest` of
  `academic_persons` with its own fixture, ported from `main` with
  `georgringer/numbered-pagination` loaded as in the other list tests of this
  branch, for the list and the list-and-detail element: a picked profile from
  another folder in the order of the selection, a hidden and a deleted picked
  profile, "Show hidden records", a paginated selection, and a guard that a
  list without a selection keeps its Startingpoint. The first eight red on
  TYPO3 v12 and v13 against the unchanged repository
  (`.agent/tmp/ace-923-p1-2/red-12-selection.log`, `red-13-selection.log`).
  The guard shown red by dropping the storage pages of every list
  (`red-12-sabotage.log`, `red-13-sabotage.log`).

## 2. Implementation

- [x] 2.1 The selection branch of the profile query no longer respects the
  storage pages, verified by 1.1 green on v12 and v13.

## 3. Documentation

- [x] 3.1 The 2.4 entry `Important-PickedProfilesIgnoreTheStartingpoint.rst`
  and the check in `Upgrade/From23To24.rst`, byte-identical with `main`, with
  every statement checked against this branch on TYPO3 v12 and v13.
- [x] 3.2 `docs/architecture/database-queries.md` explains why a selection is
  not filtered by storage pages.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j 4` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_persons`
  manual, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-923`, and
  the archive of this change with the spec sync as the last commit.
