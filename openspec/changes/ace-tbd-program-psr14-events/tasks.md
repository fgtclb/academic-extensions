## 1. Fixture listeners

- [ ] 1.1 Add a fixture extension for the programs functional tests (see
  `docs/testing/fixture-extensions.md`) carrying three listeners registered
  with TYPO3's `#[AsEventListener]`, each enabled by a test flag, and run
  `composerUpdate` so its PSR-4 namespace resolves.

## 2. Program list events

- [ ] 2.1 Add `Event/ModifyProgramDemandEvent` and dispatch it after the
  demand is built; a functional plugin test with the restricting listener
  expects only "Master" programs. Remove the dispatch, watch the test fail,
  restore it.
- [ ] 2.2 Add `Event/ModifyListProgramsEvent` and dispatch it before the
  assignment; functional tests expect the reversed order and a rendered
  additional variable, and fail with the dispatch removed.
- [ ] 2.3 Assert that without an enabled listener the existing plugin tests
  pass unchanged.

## 3. Program page event

- [ ] 3.1 Inject `ProgramDataFactory` and `EventDispatcherInterface` into
  `ProgramDataProcessor`, add `Event/ModifyProgramDataEvent` and dispatch it;
  a page test with the subtitle listener expects the replaced subtitle and
  fails with the dispatch removed.
- [ ] 3.2 Run the new tests on v13 first, then on v14.

## 4. Documentation

- [ ] 4.1 Add `academic-programs/Documentation/Developers/Events.rst`
  (arguments, dispatch point and an example listener with TYPO3's
  `#[AsEventListener]` for each event), linked from the manual's index.
- [ ] 4.2 Add `Documentation/Changelog/3.0/Feature-ProgramListAndPageEvents.rst`,
  naming the new constructor of `ProgramDataProcessor`.
- [ ] 4.3 If `docs/` carries a list of extension points by then
  (`ace-tbd-extension-point-policy`), add the three events; otherwise state in
  the pull request that `docs/` needs no change.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack and verify the
  key.
- [ ] 5.2 Rename the change to `ace-<NNN>-program-psr14-events`.
- [ ] 5.3 Commit as `[FEATURE] ACE-<NNN>: Add events to program list and
  page` in TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 6.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 6.5 Archive the change as the last commit of the pull request.
