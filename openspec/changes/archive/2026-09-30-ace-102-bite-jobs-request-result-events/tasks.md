## 1. Backport analysis

- [x] 1.1 Diff the touched files against `main`: the service is identical,
  the controller has no plugin view event and no context, the stub fixture
  and the plugin test fixtures of ACE-677 exist. `PluginControllerActionContext`
  exists in `academic_base`. TYPO3 v12 has no `#[AsEventListener]`, PHP 8.1
  no `readonly` classes.

## 2. Tests first

- [x] 2.1 Take over `BiteJobsServiceTest`, `BiteJobsApiStubTrait`,
  `BiteJobsEventsTest`, the unit test of the result event and the fixture
  extension `test_bitejobs_listener` from `main`, with the listeners
  registered in `Services.yaml`. Show the failed-call test fails against the
  unchanged service.
- [x] 2.2 Break every rule the tests cover on purpose on v12, watch the tests
  go red, restore.

## 3. Implementation

- [x] 3.1 Take over the two event classes, the stateless service as a
  `final` class with `readonly` properties, and the context the controller
  hands to the service. Verify group 2 on v12 and v13.

## 4. Documentation

- [x] 4.1 `Documentation/Developers/Index.rst` with listeners registered in
  `Services.yaml`, `Documentation/Changelog/2.4/Feature-RequestAndResultEvents.rst`,
  `Important-FailedRequestRendersNoJobs.rst`, the pointer in the 2.1 breaking
  entry and the introduction. Verify the rendering.
- [x] 4.2 `docs/`: drop the service from the stateful services in
  `dependency-injection.md`, the line numbers in
  `core-version-aware-code.md`, the fixture extension page and the test
  counts. Verify `lintMarkdown -n`.

## 5. Commit

- [x] 5.1 Commit as `[FEATURE] ACE-102: Add events to the B-ITE job list` in
  TYPO3 Core format, with `Resolves: ACE-102`.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12 (PHP 8.1) and v13 (PHP 8.2), `functional` on
  PostgreSQL with `-j 8` for both, MariaDB and MySQL for the bite jobs tests.
- [x] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.3 `docs/` and the `academic-bite-jobs` `Documentation/` changelog
  updated, `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 6.4 Archive the change as the last commit of the pull request.
