## 1. Fixture listeners

- [x] 1.1 Add a fixture extension for the programs functional tests (see
  `docs/testing/fixture-extensions.md`) carrying three listeners registered
  with TYPO3's `#[AsEventListener]`, each inert until a TypoScript setting
  asks for something, and a list template that renders a listener
  variable. Run `composerUpdate` so its PSR-4 namespace resolves.

## 2. Program list and finder events

- [x] 2.1 Add `Event/ModifyProgramDemandEvent` and dispatch it in the list
  and the finder after the demand is built. Functional plugin tests: a
  listener restricting the demand to "Master" lists only "Master" programs,
  a listener handing back a demand of its own restricted to one page lists
  only that page's programs, and the finder offers the categories of the
  "Master" programs only. Remove each dispatch, watch its test fail, restore
  it.
- [x] 2.2 Add `Event/ModifyProgramListEvent` and dispatch it in both actions
  before the assignment. Functional tests expect the reversed order, the
  replaced categories of the list and of the finder, and a rendered listener
  variable, and fail with the dispatch removed.
- [x] 2.3 Assert that without an enabled listener the existing plugin tests
  pass unchanged: they do not load the fixture extension.
- [x] 2.4 Record the context of both events in the recording listener of
  `test_plugin_view_event` and add the program list and finder to the
  renderings of `ModifyPluginViewEventTest` that assert one context per
  rendering. Build the context a second time in the finder and watch its row
  fail.

## 3. Program page event

- [x] 3.1 Inject `ProgramDataFactory` and `EventDispatcherInterface` into
  `ProgramDataProcessor`, add `Event/ModifyProgramDataEvent` and dispatch it
  before the facts are built. Page tests with the listener expect the
  replaced subtitle and the changed credit points fact, and fail with the
  dispatch removed.
- [x] 3.2 Unit tests for the getters and setters of the three events.
- [x] 3.3 Run the new tests on v13 first, then on v14.

## 4. Documentation

- [x] 4.1 Add `academic-programs/Documentation/Developers/Index.rst`
  (arguments, dispatch point, the rules the partner chapter states, and an
  example listener with TYPO3's `#[AsEventListener]` for each event),
  linked from the manual's index.
- [x] 4.2 Add `Documentation/Changelog/3.0/Feature-ProgramListAndPageEvents.rst`,
  naming the new constructor of `ProgramDataProcessor`.
- [x] 4.3 Add the three events, `ProgramDemand` and `ProgramData` to the
  extension points page of `academic_base`, tag them `@api`, and name the
  program events where the page names the partner and project ones.
- [x] 4.4 Name the program events in `docs/architecture/list-plugin-events.md`
  and add the fixture extension to `docs/testing/fixture-extensions.md`.
- [x] 4.5 Rename `ModifyListProgramsEvent` to `ModifyProgramListEvent` in
  `ace-tbd-final-partner-project-controllers`.
- [x] 4.6 Name the program list and finder events where the documentation
  and the changelog of `ace-767-shared-plugin-context` list the events that
  share one context, and in the descriptions of the list plugin events page
  in `docs/Index.md` and `docs/architecture/Index.md`.

## 5. File the issue

- [x] 5.1 After implementation, file the ACE issue in YouTrack and verify the
  key: ACE-766.
- [x] 5.2 Rename the change to `ace-766-program-psr14-events`.
- [x] 5.3 Commit as `[FEATURE] ACE-766: Add program list and page events`
  in TYPO3 Core format.

## 6. Definition of done

- [x] 6.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 6.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  change, and `README.md` and `CONTRIBUTING.md` still only summarize.
- [x] 6.5 Archive the change as the last commit of the pull request.
