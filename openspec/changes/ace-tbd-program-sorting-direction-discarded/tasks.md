## 1. Tests first

- [ ] 1.1 Ask the author of `a1aef04ed` ("[TASK] Remove sorting option by
  sorting desc") whether the removal had a reason; stop and report back if it
  had one.
- [ ] 1.2 Add `Tests/Unit/Domain/Model/Dto/ProgramDemandTest.php` in
  `academic-programs`: `setSortingField('sorting')` followed by
  `setSortingDirection('desc')` gives `getSorting() === 'sorting desc'`, and a
  pair no constant covers keeps the previous value; run it against the
  unchanged code and record that the first case fails with `sorting asc`.
- [ ] 1.3 Add a functional case to `AcademicProgramsPluginTest` that posts
  `sortingField=sorting` and `sortingDirection=desc` against three manually
  sorted programs and asserts the reversed order and the selected direction;
  record that it fails on the unchanged code.
- [ ] 1.4 Add a functional case with the FlexForm default `sorting desc` and
  record that it fails on the unchanged code.

## 2. Implementation

- [ ] 2.1 Add `SortingOptions::SORT_BY_SORTING_DESC = 'sorting desc'` and
  verify 1.2 and 1.3 pass.
- [ ] 2.2 Add the `sorting desc` item to `settings.sorting` in
  `Configuration/FlexForms/ProgramListSettings.xml` and the label
  `flexform.sorting.sorting.desc` to the English and German
  `locallang_be.xlf` (one line per source/target, two-space indentation);
  verify 1.4 passes.
- [ ] 2.3 Check that every frontend label key the sorting ViewHelper derives
  for the new value (`field`, `direction` and `combined` types) exists, and
  verify by rendering a combined select in a functional case.
- [ ] 2.4 Remove the constant again, watch 1.2 to 1.4 go red, restore it.

## 3. Documentation

- [ ] 3.1 Add
  `Documentation/Changelog/3.0/Important-ManualOrderCanBeReversed.rst` from
  `Build/Documentation/Templates/Changelog-Important.rst` and verify it is
  picked up by `Documentation/Changelog/3.0/Index.rst`.
- [ ] 3.2 Update `docs/` where the list orderings are described, or state in
  the pull request that no `docs/` page covers them.

## 4. File the issue

- [ ] 4.1 After implementation, file or confirm the ACE issue in YouTrack
  (ACE-625 covers this change) and rename the change to
  `ace-<NNN>-program-sorting-direction-discarded`.
- [ ] 4.2 Commit in TYPO3 Core format, `[BUGFIX] ACE-<NNN>: <subject>`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch 2 after a backport analysis
  (`docs/workflow/backporting.md`).

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog updated in the same
  change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
