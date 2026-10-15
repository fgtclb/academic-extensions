## 1. Tests first, red on the unchanged code

- [x] 1.1 `FrontendPluginRenderingTrait`: `frontendSessionCookie()`,
  `followSeeOther()` and cookie parameters for `submitFrontendForm()`, used
  by the tests below.
- [x] 1.2 `AcademicJobsNewJobFormRedirectPageTest` rewritten: back to the
  form without a redirect page (none, listener 0, listener -1, listener 0
  with `listPid`, element and site setting naming the form page), the
  confirmation once, mode "never", the redirect page of the element, the site
  setting and a listener, the conditional mode, the deprecated `listPid` with
  its deprecation, and a confirmation left on an unrendered redirect page.
  Run on TYPO3 v13 and v14 against the unchanged controller and template:
  all 14 red, on v13 by the `200` of the answer, on v14 by the list plugin
  arguments in the `Location` or by the page rendered into the body. Logs in
  `.agent/tmp/ace-912/red-core13-*.log` and `red-core14-*.log`.
- [x] 1.3 `AcademicJobsNewJobFormRejectedSubmissionTest`: a rejected
  submission shows no error message of Extbase (red on v13 and v14), and the
  confirmation after a rejected submission comes without it (red on v13,
  green on v14, which does not keep the message in the session).
- [x] 1.4 `AcademicJobsNewJobFormFailingMailTest`: the form shows the warning
  without a redirect page (red on v13 and v14), and the redirect test runs on
  v13 as well.

## 2. Implementation

- [x] 2.1 `AfterSaveDecision::$returnsToForm`, set by
  `AfterSaveResolver::decide()`, covered in `AfterSaveResolverTest` for every
  mode.
- [x] 2.2 `JobController`: the queue constant, the two thrown exits with
  `clearCachesOfRegisteredPageIds()` before them, the null uid branch through
  the form exit, `listPid` read with `?? 0`, `getErrorFlashMessage()`
  returning `false`, the `@todo` block replaced. The tests of group 1 turn
  green on v13 and v14. A test of the cache clearing goes red on v14 with
  the call removed, and the phpstan baseline entry for the null uid passed
  to the mail is dropped.
- [x] 2.3 `Templates/Job/New.html` renders the queue of the form inside the
  content element.
- [x] 2.4 Labels of the items `-1` and `0` in `locallang.xlf` and
  `de.locallang.xlf`, the German keys aligned with the English ones, the
  description of the mode in `Sets/Full/settings.definitions.yaml`.
- [x] 2.5 `AcademicJobsLabelOverrideTest` follows the redirect to read the
  confirmation, the base test cases of the notification mail assert the `303`
  on both cores, the unused response recorder of the fixture extension is
  removed.
- [x] 2.6 Review follow-up: the redirect persists before it clears the caches,
  so a change an after-save listener does not persist itself is stored, and a
  request without a job returns to the form. Both tests went red on v14 without
  the change, logs `.agent/tmp/ace-912/red-core14-persist.log` and
  `red-core14-nojob.log`. The label of item `-1` reads "Use the site or
  TypoScript setting".

## 3. Documentation

- [x] 3.1 Manual of `academic_jobs`: `Integrator/JobForm.rst`,
  `Integrator/Index.rst`, `Developers/Events/AfterSaveJobEvent.rst`,
  `Styling/Index.rst`, `Configuration/General/Index.rst`,
  `Templates/Override/Index.rst`, and `Styling/Index.rst` of `academic_base`.
- [x] 3.2 `Changelog/2.4/Important-RedirectPageZeroFromAListenerMeansNone.rst`
  updated and a new `Changelog/2.4/Important-*.rst`, both written so that
  branch `2` can take them byte for byte.
- [x] 3.3 `Upgrade/From23To24.rst` (identical on branch `2`) and
  `Upgrade/From23To30.rst`: the listener note and the line on an overridden
  `New.html`.
- [x] 3.4 `docs/architecture/frontend-forms.md`,
  `docs/testing/functional-tests.md`, `docs/testing/testing-helper.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp` green.
- [x] 4.2 `composerUpdate -t 13`, then `cgl -n`, `phpstan`, `unit` and
  `functional -j auto` (SQLite) for TYPO3 v13 green: TYPO3 13.4.36, unit
  2091 tests, functional 5744 tests in 16 chunks.
- [x] 4.3 `composerUpdate -t 14`, then the same suites for TYPO3 v14 green:
  TYPO3 14.3.9, unit 2086 tests, functional 5763 tests in 16 chunks.
- [x] 4.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.5 `README.md` and `CONTRIBUTING.md` still only link.
- [x] 4.6 One `[BUGFIX]` commit in TYPO3 Core format with `Resolves: ACE-912`
  and `Resolves: ACE-919`, no attribution of a tool.
- [x] 4.7 The change archived as the last commit, the main specs checked
  afterwards.
