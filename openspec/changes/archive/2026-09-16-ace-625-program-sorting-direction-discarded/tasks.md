## 1. Tests first

- [x] 1.1 Update `Tests/Unit/Domain/Model/Dto/ProgramDemandTest.php`, which
  pins the defect: `aFieldOfferedInOneDirectionOnlyRejectsTheOther()` became
  `theManualOrderCanBeReversed()`, and `knownSortingOptions()` gained the new
  option. `anUnknownOptionIsIgnored()` is unchanged and still proves that a
  pair no constant covers keeps the previous value. Red on the unchanged code:
  `Undefined constant …SortingOptions::SORT_BY_SORTING_DESC`.
- [x] 1.2 Update `Tests/Unit/Enumeration/SortingOptionsTest.php`, whose
  `everySortingOptionIsOffered()` asserts the constant map as a whole, and
  correct the docblock describing the asymmetry as intended. Red on the
  unchanged code, same error.
- [x] 1.3 Extend `SORTING_OPTIONS` in
  `Tests/Functional/Routing/ProgramListRouteEnhancerTest.php` by the new pair
  and correct its comment. **Proven red by mutation on this branch**, not
  inherited from `main`: with the constant removed,
  `everyGeneratedSortingUriResolvesBackIntoItsArguments()` fails (7 tests, 1
  failure) because the direction select renders `asc` for the demanded `desc`;
  with it restored, 7 tests / 59 assertions pass. The file was restored
  byte-identical (md5 `8649469a7e3815be8d2b6db80b678f5c` before and after).
- [x] 1.4 Stated in the pull request: no plugin test covers the rendered list
  on this branch, because `academic_programs` has no `Tests/Functional/Plugins/`
  directory here. Adding one is new test infrastructure, not a backport.

## 2. Implementation

- [x] 2.1 Add `SortingOptions::SORT_BY_SORTING_DESC = 'sorting desc'` to the
  enumeration, which extends the core `Enumeration` base class on this branch.
- [x] 2.2 Add the `sorting desc` item to `settings.sorting` in **both**
  `Configuration/FlexForms/Core12/ProgramListSettings.xml` and
  `Configuration/FlexForms/Core13/ProgramListSettings.xml`.
- [x] 2.3 Add the label `flexform.sorting.sorting.desc` to the English and
  German `locallang_be.xlf`, tab indented as the rest of those files are.
- [x] 2.4 Confirmed: `sorting.field.sorting` and `sorting.direction.desc`
  already exist in `locallang.xlf` and `de.locallang.xlf`, so no frontend
  label is added. The `combined` type has no label for any option on either
  branch; unchanged here.
- [x] 2.5 Satisfied by the mutation in 1.3 — the constant was removed, the
  reproduction went red, and the file was restored byte-identical.

## 3. Documentation

- [x] 3.1 Add
  `Documentation/Changelog/2.4/Important-ManualOrderCanBeReversed.rst`, picked
  up by the `Important-*` glob of `Documentation/Changelog/2.4/Index.rst`.
  `checkRstRenderingSingle academic-programs` is green.
- [x] 3.2 Correct `Documentation/Configuration/RouteEnhancers/Index.rst`, which
  documented that `/sorting/desc` resolves to an option the plugin never
  offers. No `docs/` page on this branch describes the list orderings; stated
  in the pull request.

## 4. Issue and commit

- [x] 4.1 No new issue: ACE-625 carries the `[3.x][2.x]` prefix and Version
  2.4.0 and covers both lines. The backport pull request is linked on it.
- [x] 4.2 Commit in TYPO3 Core format, `[BUGFIX] ACE-625: …`, with
  `Releases: 2`.

## 5. Definition of done

- [x] 5.1 `-t 12 -p 8.1`: `composerUpdate`, `lintPhp`, `cgl -n` (0 of 670),
  `phpstan` (no errors), `unit` (656 tests) and `functional` (1254 tests, 2
  skipped) all green.
- [x] 5.2 `-t 13 -p 8.2`: `composerUpdate`, `lintPhp`, `cgl -n` (0 of 670),
  `phpstan` (no errors), `unit` (656 tests) and `functional` (1365 tests, 4
  skipped) all green.
- [x] 5.3 `functional -d postgres` for `academic_programs` green on **both**
  core versions (86 tests on v12, 100 on v13), because this change alters an
  ordering.
- [x] 5.4 `lintMarkdown -n` (127 files, 0 problems) and
  `checkRstRenderingSingle academic-programs` green.
- [ ] 5.5 Archive the change as the last commit of the pull request.
