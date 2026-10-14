## 1. Premises, dependencies and decisions

- [ ] 1.1 Re-check every statement of `design.md`, *Context*, on `main` `HEAD`
  for TYPO3 v13 and v14. A premise that no longer holds stops the work, and
  the change is updated with `/opsx:update` first.
- [ ] 1.2 Check the changes this one follows: `ace-438-persons-list-ordering-input`
  merged with the rule for entries without a label or a value (ACE-434 item
  3), `ace-672-partner-select-offers` merged and whether it removed the null
  check of the partner select (ACE-443 item 5, it plans to leave it), and
  `ace-624-last-updated-sorting` postponed to 3.1.0 and not merged, so
  `PartnerRepository::findByDemand()` and `SortingOptions` of
  `academic-projects` are as ACE-672 left them. Record the outcome in
  the pull request. If ACE-438 did not take item 3, update this change before
  writing code.
- [ ] 1.3 Confirm with the maintainer decision 7 (two fields on one column)
  and decision 8 (the two renames). Answered on 2026-10-14: 7 is B, the
  later field keeps winning and the upgrade check and the persons settings
  status name the column, so the `academic-persons/validation-flags` delta
  is dropped. 8 is both renames. Decision 1 is one pull request with seven
  commits.

## 2. ACE-434: guarded reads (commit 1)

- [ ] 2.1 Add a case to `academic-programs/Tests/Functional/Factory/ProgramDataFactoryTest.php`
  that maps a row holding only a `uid` and expects zeros and empty strings
  for every other property. Show it red on the unchanged factory (PHP
  warning under `failOnWarning`), then read the ten keys with `??` as the two
  ACE-777 columns do, and show it green.
- [ ] 2.2 Add a case per extension to the demand factory tests of
  `academic-projects` (`DemandFactorySettingsTest`), `academic-partners` and
  `academic-programs`: `settings.categories` set and an empty content element
  row. Show each red on the unchanged factory, then read the uid with
  `?? 0` in all three, and show them green.
- [ ] 2.3 phpstan and unit for v13 and v14, the functional tests of the three
  `Tests/Functional/Factory/` folders on both cores, then commit as
  `[BUGFIX] ACE-434: Guard program and demand reads` with
  `Resolves: ACE-434` and `Related: ACE-438`.

## 3. ACE-435: redirect page 0 (commit 2)

- [ ] 3.1 In `academic-base/Tests/Unit/Form/AfterSaveResolverTest.php`, expect
  no redirect page for the "redirect page zero" rows and add rows for a
  negative page. Show them red, then map a page of 0 or below to `null` in
  `AfterSaveResolver::decide()` and show them green. Replace the docblock of
  `decide()`, which describes the disagreement.
- [ ] 3.2 A functional test of the new-job form with a fixture extension
  whose listener of the after-save event sets the redirect page to 0: the
  answer goes to the form page and the confirmation message is shown, on v13
  and v14 (v13 sends the redirect with `header()`, v14 answers it, as
  `AcademicJobsLabelOverrideTest` handles it). The fixture key sorts after
  `academic_jobs`. Show it red by reverting the change of 3.1 once.
- [ ] 3.3 Name the `> 0` condition in the docblock of
  `FlashMessageCreationMode::shouldBeCreated()`, and correct the mode name in
  the comment of `JobController::createAction()`.
- [ ] 3.4 `academic_jobs` `Documentation/Changelog/3.0/Important-*.rst`: a
  redirect page of 0 or below from a listener now means the form page with
  the message of the configured mode, and the deprecated `listPid` fallback
  is not used for it.
- [ ] 3.5 phpstan and unit for v13 and v14, the jobs functional tests on both
  cores, then commit as `[BUGFIX] ACE-435: Treat redirect page 0 as none`
  with `Resolves: ACE-435`.

## 4. ACE-436: nullable uid (commit 3)

- [ ] 4.1 Unit tests in `academic-partners/Tests/Unit/Domain/Model/PartnerTest.php`:
  an unsaved partner has no uid, and its attributes are an empty collection.
  In `academic-projects/Tests/Unit/Domain/Model/ProjectTest.php`: an unsaved
  project has neither uid nor pid. Show each red with the `TypeError` of
  today.
- [ ] 4.2 Type `Partner::getUid()`, `Project::getUid()` and
  `Project::getPid()` as `?int` with the docblocks of the parent, let
  `Partner::getAttributes()` return an empty collection without a uid, and
  keep the `int` parameter of `GeocodeCommand` without a new baseline entry.
  Remove the four baseline entries of each core version, and show the tests
  of 4.1 green.
- [ ] 4.3 phpstan and unit for v13 and v14, the functional tests of
  `academic-partners` and `academic-projects` on both cores, then commit as
  `[BUGFIX] ACE-436: Allow no uid on unsaved entities` with
  `Resolves: ACE-436`.

## 5. ACE-441: sanitizer per setter call (commit 4)

- [ ] 5.1 A unit test in `academic-persons/Tests/Unit/Domain/Model/ProfileTest.php`:
  call a sanitizing setter, then register a spy builder with
  `GeneralUtility::addInstance()` and assert that the next setter sanitizes
  through it. A second test registers the spy once and calls a storage setter
  with two elements: all six values pass the spy, and the instance stack is
  empty afterwards, so the setter built exactly one sanitizer. Show both red
  against the static of today.
- [ ] 5.2 Drop the static from `Profile::getHtmlSanitizer()`, build the
  sanitizer once per setter call into a local variable, and show the new and
  all existing `ProfileTest` cases green, the encoded link of ACE-425
  included.
- [ ] 5.3 phpstan and unit for v13 and v14, the functional tests of
  `academic-persons` and `academic-persons-edit` on both cores, then commit
  as `[TASK] ACE-441: Build profile sanitizer per setter` with
  `Resolves: ACE-441` and `Related: ACE-425`.

## 6. ACE-443: dead code (commit 5)

- [ ] 6.1 Remove `FileReferenceCollection` and its functional test, drop the
  rows of that test from the four `Build/phpunit/FunctionalTestTimes-*.json`,
  and confirm with a search that nothing in `packages/` names the class any
  more, apart from the 2.4 changelog, which stays as it is.
- [ ] 6.2 `academic_programs` `Documentation/Changelog/3.0/Breaking-*.rst`: the
  class is removed, the core `FileRepository::findByRelation()` replaces it.
- [ ] 6.3 Task 1.2 found the null check still in place, so drop the `if`
  of the partner select, declare the result interface the query returns on
  `findAll()`, remove its baseline entry on both cores, adapt
  `PartnerRepositoryFindAllTest` to the declared type (the nullable
  parameter of its `resultUids()` helper and
  `anEmptyResultIsAQueryResultAndNotNull()`), and add a
  `Breaking-*.rst` in `academic_partners` for the changed return type. The
  declared return type is the proof that the check was dead, phpstan 1.12 at
  level 8 does not report the always true comparison.
- [ ] 6.4 The `#[Test]` of
  `PluginControllerActionContextTest::getRequestReturnsExpectedRequest()`
  came with ACE-748. Only show the test red once against another request
  object, and restore.
- [ ] 6.5 phpstan and unit for v13 and v14, the functional tests of
  `academic-programs`, `academic-partners` and `academic-persons` on both
  cores, then commit as `[!!!][TASK] ACE-443: Remove coverage round dead code`
  with `Resolves: ACE-443` and `Related: ACE-672`. The commit message names
  the validator guard as won't fix and why.

## 7. ACE-444: slips (commit 6)

- [ ] 7.1 Correct the header of the seven files listed in `design.md` to
  `academic_persons`, and `@toodo` to `@todo` in `Profile.php`. A search for
  `"academic_persons_edit" Extension` below `academic-persons` and for
  `@toodo` in `packages/` finds nothing.
- [ ] 7.2 Decision 7 B. `TcaValidationMerger` stays as it is, and
  `twoValidationsOnOneColumnKeepTheLastTcaConfig()` stays the proof that
  the later fragment wins. Add an `@internal` interface for finding providers
  to `academic_base`, collected by `ConfigurationChecker` through a service
  tag, and a provider in `academic_persons` that reports every column two
  fields of one validation set of the persons settings name (profile,
  contracts, the three contact sets, the profile information sections per
  record type) as a warning with table, column and both fields. A functional
  test of the upgrade check with a settings fixture that declares a second
  profile field on the teaching area column, red before the provider
  exists, and one without such a field that reports nothing. The same
  warning in the persons settings entry of the status report
  (`LegacySettingsStatus`), with a test shown red the same way. The persons
  settings manual says that the later field wins. No changelog entry.
- [ ] 7.3 Move `Dto\Syncronizer\` to
  `Dto\Synchronizer\`, rename the property to `$recordSynchronizer` in
  `SynchronizerContext` and in the persons edit listener, rename the tests of
  `academic-persons`, `academic-persons-edit` and `academic-contact4pages`
  and the test class `RecordSyncronizerTest`, and update
  `Documentation/Developers/Index.rst` and `docs/architecture/class-design.md`.
  A search for `yncronizer` in the repository finds nothing outside the 3.0
  `Breaking-*.rst` of `academic_persons` and `openspec/changes/`. A
  `Breaking-*.rst` in `academic_persons`.
- [ ] 7.4 `SORT_BY_STARTDATE_desc` to
  `SORT_BY_STARTDATE_DESC` and `SortingOptionsTest`, no alias constant. A
  `Breaking-*.rst` in `academic_projects` says that stored values are
  unaffected.
- [ ] 7.5 phpstan and unit for v13 and v14, the functional tests of the
  touched extensions on both cores, then commit as
  `[!!!][TASK] ACE-444: Fix the coverage round slips`, with
  `Resolves: ACE-444`. The message names item 6 as won't fix, item 9 as
  fixed by ACE-711 and the other items done elsewhere. The updates of this
  change for decisions 7 and 8 go into the same commit.

## 8. Definition of done

- [ ] 8.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit`, and `functional` on SQLite (`-j auto`), PostgreSQL
  (`-b docker -j 8`) and MariaDB (`-j 8`) green on the head of the branch.
- [ ] 8.2 The same after `composerUpdate` for TYPO3 v14.
- [ ] 8.3 `lintMarkdown -n` and `checkRstRenderingAll` green, and no rendered
  changelog entry produces a definition list.
- [ ] 8.4 `docs/` updated where it names what changed
  (`docs/architecture/class-design.md` with the rename), and `README.md` and
  `CONTRIBUTING.md` still only summarize.
- [ ] 8.5 Every commit of the pull request is green on its own for `phpstan`
  and `unit` on both cores, each message in TYPO3 Core format with its
  verified key, and no attribution of any kind.
- [ ] 8.6 Archive the change as the last commit,
  `[TASK] ACE-434: Archive the coverage round leftovers`, with a `Related:`
  line for each of the six keys.
