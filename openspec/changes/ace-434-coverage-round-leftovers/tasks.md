## 1. Premises

- [x] 1.1 Re-check the context of `design.md` on `HEAD` for TYPO3 v12 and v13
  after a `composerUpdate` each, the v12 core reads with file and line. A
  premise that no longer holds stops the work, and the change is updated
  first.

## 2. ACE-434: guarded reads (commit 1)

- [x] 2.1 Add `aRecordWithoutThePageColumnsMapsToEmptyValues()` to
  `academic-programs/Tests/Functional/Factory/ProgramDataFactoryTest.php`,
  without the application link assertions of `main`, and
  `aContentElementRecordWithoutUidGetsAnEmptyFilter()` to the demand factory
  tests of `academic-partners`, `academic-programs` and `academic-projects`.
  Show each red on the unchanged factories on v12 and v13 (PHP warning under
  `failOnWarning`).
- [x] 2.2 Read the ten keys and the content element uid with a fallback, and
  show the tests green on both cores.
- [x] 2.3 Gates on both cores, the functional tests of the three extensions,
  then commit 1 of `design.md`.

## 3. ACE-435: redirect page 0 (commit 2)

- [ ] 3.1 `academic-jobs/Tests/Unit/Event/AfterSaveJobEventTest.php`: a later
  listener reads the 0 or negative page an earlier one set. Show it red once
  against a setter that normalises.
- [ ] 3.2 The fixture extension `test_job_after_save_listener` (a listener
  setting the redirect page from a TypoScript setting, and a recorder of the
  response of the form plugin, both registered by tag) and the functional
  test `AcademicJobsNewJobFormRedirectPageTest` with the data sets 0, -1, and
  0 with a list page configured: the recorded answer is a 303 to `/new-job`
  with a `cHash`, and the confirmation message is shown. Show it red on the
  unchanged controller on v12 and on v13.
- [ ] 3.3 Normalise the redirect page in `createAction()` after the
  `listPid` fallback, correct the docblock of `shouldBeCreated()`, the
  controller comment and the comment of `FlashMessageCreationModeTest`, and
  drop the two `>= 0` guards. Show the tests green on both cores.
- [ ] 3.4 `Important-RedirectPageZeroFromAListenerMeansNone.rst` in
  `academic-jobs/Documentation/Changelog/2.4/`, with the redirect target
  before the fix as measured on v12 and v13, and the new fixture extension in
  `docs/testing/fixture-extensions.md` with the counts measured again.
- [ ] 3.5 Gates on both cores, the functional tests of `academic-jobs`, then
  commit 2.

## 4. ACE-436: nullable uid (commit 3)

- [ ] 4.1 `PartnerTest` (no uid, no categories), the new `ProjectTest` cases
  and `academic-partners/Tests/Unit/Command/GeocodeCommandTest.php`. Show them
  red with the `TypeError` on v12 and v13, and `Project::getPid()` alone once.
- [ ] 4.2 `?int` on the three getters, the empty collection, the null check
  of `GeocodeCommand` before the request, the four baseline entries removed
  in `Core12` and `Core13`, none added. The baseline sizes in
  `docs/development/quality-gates.md` and
  `docs/architecture/core-version-aware-code.md` measured again.
- [ ] 4.3 Gates on both cores, the functional tests of `academic-partners`
  and `academic-projects`, then commit 3.

## 5. ACE-441: sanitizer per setter call (commit 4)

- [ ] 5.1 The two `ProfileTest` cases of `main` with the spy builder, red
  against the method static on v12 and v13.
- [ ] 5.2 The setters build one sanitizer per call, every `ProfileTest` case
  green, `anAmpersandInAProfileInformationLinkIsHtmlEncoded()` included.
- [ ] 5.3 Gates on both cores, the functional tests of `academic-persons` and
  `academic-persons-edit`, then commit 4.

## 6. ACE-443: dead code (commit 5)

- [ ] 6.1 Deprecate `FileReferenceCollection` with an `E_USER_DEPRECATED`, and
  assert the notice in every test of `FileReferenceCollectionTest`. Show the
  suite red without `#[IgnoreDeprecations]`, and the capture assertion red
  without the `trigger_error()`.
- [ ] 6.2 In the same file: `@throws \Doctrine\DBAL\Exception` (checked on
  DBAL 3 for v12 and DBAL 4 for v13), the unused import and the unused
  parameter removed.
- [ ] 6.3 Remove `ProfileFactory::createFromFormData()` and its test, with
  `createdProfile.csv` if nothing else uses it. A search finds no caller.
- [ ] 6.4 `Deprecation-FileReferenceCollection.rst` in
  `academic-programs/Documentation/Changelog/2.4/`, with the core
  replacement and its restriction and workspace behaviour on v12 and v13.
- [ ] 6.5 Gates on both cores, the functional tests of `academic-programs`
  and `academic-persons-edit`, then commit 5.

## 7. ACE-444: slips (commit 6)

- [ ] 7.1 The nine headers, `@toodo`, strict types in
  `Settings/Validation.php`, and `docs/architecture/class-design.md` updated.
  A search finds no header and no `@toodo` left.
- [ ] 7.2 `ProfileInformationType::__set_state(): self`, and `isValid()`
  requires a field name, with the row of `ProfileInformationTypeTest`
  flipped first and shown red on v12 and v13.
- [ ] 7.3 One sentence on two spellings of one property in
  `academic-persons/Documentation/Configuration/Validations/Index.rst` and
  `docs/architecture/validation-settings.md`.
- [ ] 7.4 Gates on both cores, the functional tests of `academic-persons`,
  then commit 6.

## 8. Definition of done

- [ ] 8.1 After `composerUpdate` for TYPO3 v12: `lintPhp`, `cgl -n`,
  `phpstan`, `unit`, and `functional` on SQLite (`-j auto`), PostgreSQL
  (`-b docker -j 8`) and MariaDB (`-j 8`) green on the head of the branch.
- [ ] 8.2 The same after `composerUpdate` for TYPO3 v13.
- [ ] 8.3 `lintMarkdown -n` and `checkRstRenderingAll` green, and no rendered
  changelog entry produces a definition list.
- [ ] 8.4 `docs/` names what changed, and `README.md` and `CONTRIBUTING.md`
  still only summarize.
- [ ] 8.5 Every commit green on its own for `phpstan` and `unit` on both
  cores, each message in TYPO3 Core format with its verified key, and no
  attribution of any kind.
- [ ] 8.6 Archive the change as the last commit,
  `[TASK] ACE-434: Archive the coverage leftovers for 2`, with a `Related:`
  line for each of the six keys.
