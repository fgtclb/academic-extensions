## 1. Tests first

- [x] 1.1 Establish whether the removal in `a1aef04ed` had a reason. Waived by
  the maintainer on 2026-09-16 after the record was presented: deliberate,
  coordinated with `63aa6a508`, no reason written down, both sibling
  extensions kept the constant. The evidence is recorded in `design.md`
  instead of asking the author.
- [x] 1.2 Update `Tests/Unit/Domain/Model/Dto/ProgramDemandTest.php`, which
  pinned the defect: `aFieldOfferedInOneDirectionOnlyRejectsTheOther()` became
  `theManualOrderCanBeReversed()`, and `knownSortingOptions()` gained the new
  option. `anUnknownOptionIsIgnored()` still proves a pair no constant covers
  keeps the previous value. Red on unchanged code: `Error: Undefined constant
  …SortingOptions::SORT_BY_SORTING_DESC`.
- [x] 1.3 Update `Tests/Unit/Enumeration/SortingOptionsTest.php`, whose
  `everySortingOptionIsOffered()` asserts the constant map as a whole, and
  correct the docblock describing the asymmetry as intended. Red on unchanged
  code: the expected map misses `'SORT_BY_SORTING_DESC' => 'sorting desc'`.
- [x] 1.4 Add `programListPluginReversesTheManualOrderWhenDemanded()` to
  `AcademicProgramsPluginTest`: posts `sortingField=sorting` plus
  `sortingDirection=desc` against programs whose `sorting` values are
  unrelated to uid and title order, and asserts the reversed list and the
  selected direction. Red on unchanged code.
- [x] 1.5 Add `programListPluginSortsProgramsByReversedManualOrderWhenConfigured()`
  with the FlexForm default `sorting desc`. Red on unchanged code.
- [x] 1.6 Extend `SORTING_OPTIONS` in
  `Tests/Functional/Routing/ProgramListRouteEnhancerTest.php` by the new pair
  and correct its comment. This turned out to go red on unchanged code as
  well — `everyGeneratedSortingUriResolvesBackIntoItsArguments()` renders each
  pair and asserts the selected option — so it is a third reproduction rather
  than mere coverage. The list stays spelled out, so a future option still has
  to be added by hand; that is stated in its comment.

## 2. Implementation

- [x] 2.1 Add `SortingOptions::SORT_BY_SORTING_DESC = 'sorting desc'`. 1.2,
  1.3, 1.4 and 1.6 pass with it.
- [x] 2.2 Add the `sorting desc` item to `settings.sorting` in
  `Configuration/FlexForms/ProgramListSettings.xml` and the label
  `flexform.sorting.sorting.desc` to the English and German
  `locallang_be.xlf`, one line per source/target, two-space indentation. 1.5
  passes with it.
- [x] 2.3 The two frontend keys the ViewHelper derives for the new value,
  `sorting.field.sorting` and `sorting.direction.desc`, already exist in
  `locallang.xlf` and `de.locallang.xlf`, so no frontend label is added. The
  `combined` type has had no label for any option since `8e27dd444` removed
  them; that is unchanged here and is stated in the pull request.
- [x] 2.4 Proven by sequence rather than by re-removal: all six tests were
  written first and run against the unchanged enumeration, where they failed
  (unit 2 errors + 1 failure, functional 3 failures), and pass after the
  constant was added. Removing it again would repeat exactly that experiment.

## 3. Documentation

- [x] 3.1 Add
  `Documentation/Changelog/3.0/Important-ManualOrderCanBeReversed.rst`, picked
  up by the `Important-*` glob of `Documentation/Changelog/3.0/Index.rst`.
  `checkRstRenderingSingle academic-programs` is green.
- [x] 3.2 Correct `Documentation/Configuration/RouteEnhancers/Index.rst`,
  which documented that `/sorting/desc` resolves to an option the plugin never
  offers. No `docs/` page describes the list orderings — the only match is a
  generic `setOrderings()` mention in `docs/architecture/database-queries.md` —
  so nothing is updated there; stated in the pull request.

## 4. File the issue

- [x] 4.1 ACE-625 covers this change and already exists, so no new issue is
  filed. The change is renamed to
  `ace-625-program-sorting-direction-discarded`. Its `Type` is `Task` with no
  `Version` set, and its summary is German; changing either is a write and is
  left to the maintainer.
- [ ] 4.2 Commit in TYPO3 Core format, `[BUGFIX] ACE-625: <subject>`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch 2 after a backport analysis
  (`docs/workflow/backporting.md`).

## 6. Definition of done

- [x] 6.1 `-t 13`: `composerUpdate`, `lintPhp`, `cgl -n` (0 of 785),
  `phpstan` (no errors), `unit` (818 tests) and `functional` (2066 tests, 8
  skipped) all green.
- [x] 6.2 `-t 14`: `composerUpdate`, `lintPhp`, `cgl -n` (0 of 785),
  `phpstan` (no errors), `unit` (813 tests) and `functional` (2081 tests, 6
  skipped) all green. `functional -d postgres` for `academic-programs` is
  green as well (165 tests), because this change alters an ordering.
- [x] 6.3 `lintMarkdown -n` green (592 files, 0 problems) and
  `checkRstRenderingSingle academic-programs` green.
- [x] 6.4 The `Documentation/` changelog and the route enhancer page are
  updated in this change; no `docs/` page covers the list orderings.
- [ ] 6.5 Archive the change as the last commit of the pull request.
