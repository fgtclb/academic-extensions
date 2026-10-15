## 1. Tests first, red on the unchanged code

- [x] 1.1 `FrontendPluginRenderingTrait`: `frontendSessionCookie()` and
  `followSeeOther()`, and `postJob()` of the notification mail test case
  sends a session cookie when it gets one, used by the tests below.
- [x] 1.2 `AcademicJobsNewJobFormRedirectPageTest` rewritten as on `main`:
  back to the form without a redirect page (none, listener 0, listener -1,
  listener 0 with `listPid`, element and TypoScript naming the form page), the
  confirmation once, mode "never", the redirect page of the element, the
  TypoScript constant and a listener, the conditional mode, the deprecated
  `listPid` with its deprecation, a confirmation left on an unrendered
  redirect page, and the cache clearing. Run on TYPO3 v12 and v13 against the
  unchanged controller and template: all 15 red, by the `200` of the answer
  that rendered the page.
- [x] 1.3 `AcademicJobsNewJobFormRejectedSubmissionTest`: a rejected
  submission shows no error message of Extbase, and the confirmation after a
  rejected submission comes without it. Both red on v12 and v13, the second
  one because both cores kept the message in the session.
- [x] 1.4 `AcademicJobsNewJobFormFailingMailTest`: the form shows the warning
  without a redirect page, the redirect to the configured page is asserted,
  and the base test case asserts the `303` with an empty body. All 7 red on
  v12 and v13 by the status `200`.
- [x] 1.5 `NewJobFormFlexFormTest`: the labels of the message mode items in
  English and German, red on v12 and v13 with the old labels.
- [x] 1.6 `AcademicJobsNewJobFormRedirectPageTest`: a fixture listener that
  changes the title of the saved job and hands it to the repository without
  persisting it, and a request to the saving action without a job. The first
  red on v12 and v13 with the thrown redirect but without `persistAll()`, the
  second red on the unchanged code by the `200` of `redirect('new')`.
- [x] 1.7 `AcademicJobsLabelOverrideTest` follows the redirect to read the
  confirmation from the form, its 4 cases of a sent form red on v12 and v13
  by the missing `Location`.

## 2. Implementation

- [x] 2.1 `JobController`: the queue constant, "returns to the form" derived
  inline, the two thrown exits with `persistAll()` and
  `clearCachesOfRegisteredPageIds()` before them, a request without a job
  and the null uid branch through the form exit, `listPid` read with `?? 0`,
  `getErrorFlashMessage()` returning `false`, the `@todo` block replaced. The
  tests of group 1 turn green on v12 and v13. The cache clearing test goes
  red on v12 and v13 with the call removed. The phpstan baseline entry of
  both cores for the null uid passed to the mail is dropped.
- [x] 2.2 `Templates/Job/New.html` renders the queue of the form inside the
  content element.
- [x] 2.3 Labels of the items `-1` and `0` in `locallang.xlf` and
  `de.locallang.xlf`, the German keys aligned with the English ones, the
  descriptions of both settings in `Sets/Full/settings.definitions.yaml`.
- [x] 2.4 The response recorder of `test_job_after_save_listener` removed,
  nothing uses it any more. `AcademicJobsNewJobFormImageUploadTest` asserts
  the `303` with an empty body of a saved job, as the mail tests do.

## 3. Documentation

- [x] 3.1 `Changelog/2.4/Important-RedirectPageZeroFromAListenerMeansNone.rst`,
  the new `Changelog/2.4/Important-JobFormReturnsToItsPage.rst` and
  `Upgrade/From23To24.rst` byte-identical with `main`.
- [x] 3.2 Manual of `academic_jobs`: `Configuration/General/Index.rst` and
  `Templates/Override/Index.rst`, which carries the redirect page snippet on
  this branch, as it has no integrator chapter.
- [x] 3.3 `docs/testing/functional-tests.md`, `docs/testing/testing-helper.md`
  and `docs/testing/fixture-extensions.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp` green.
- [x] 4.2 `composerUpdate -t 12`, then `cgl -n`, `phpstan`, `unit` and
  `functional -j auto` (SQLite) for TYPO3 v12 green: TYPO3 12.4.45, unit
  1136 tests, functional 2382 tests in 16 chunks.
- [x] 4.3 `composerUpdate -t 13`, then the same suites for TYPO3 v13 green:
  TYPO3 13.4.36, unit 1136 tests, functional 2510 tests in 16 chunks.
- [x] 4.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.5 `README.md` and `CONTRIBUTING.md` still only link.
- [x] 4.6 One `[BUGFIX]` commit in TYPO3 Core format with `Resolves: ACE-912`
  and `Resolves: ACE-919`, no attribution of a tool.
- [x] 4.7 The change archived as the last commit, the main specs checked
  afterwards.
