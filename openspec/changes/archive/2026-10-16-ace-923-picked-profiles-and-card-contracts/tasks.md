## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicPersonsSelectionOutsideStartingpointTest` of
  `academic_persons` with its own fixture, for the list and the list-and-detail
  element: a picked profile from another folder in the order of the
  selection, a hidden and a deleted picked profile, "Show hidden records", a
  paginated selection, and a guard that a list without a selection keeps its
  Startingpoint. The first eight red on TYPO3 v13 and v14 against the
  unchanged repository (`.agent/tmp/ace-923-p1/red13-*.log`,
  `red14-*.log`). The guard shown red by dropping the storage pages of every
  list (`break13-storage-guard.log`).
- [x] 1.2 `AcademicPersonsContractDisplayPolicyTest`: a card with a unit, a
  function type and both restrictions stored with the option, and one that
  also shows the first contract only. All four red on v13 and v14 against the
  unchanged card action.

## 2. Implementation

- [x] 2.1 The selection branch of the profile query no longer respects the
  storage pages, verified by 1.1 green on v13 and v14.
- [x] 2.2 The card action drops the stored contract restriction option before
  the settings reach the view, verified by 1.2 green on v13 and v14.

## 3. Documentation

- [x] 3.1 `Integrator/PersonList.rst`, `PersonListAndDetails.rst` and
  `PersonContactCard.rst` of `academic_persons` describe the selection and the
  card as they behave now.
- [x] 3.2 The 2.4 entry `Important-PickedProfilesIgnoreTheStartingpoint.rst`,
  true on TYPO3 v12 and v13 and with references that resolve on branch `2`,
  and a check in `Upgrade/From23To24.rst` and `Upgrade/From23To30.rst`.
- [x] 3.3 The 3.0 entry `Feature-ContractDisplayPolicy.rst` states that a
  card ignores a stored restriction option.
- [x] 3.4 `docs/architecture/database-queries.md` explains why a selection is
  not filtered by storage pages.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_persons`
  manual, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-923`, and
  the archive of this change with the spec sync as the last commit.
