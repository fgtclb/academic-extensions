## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicJobsNewJobFormUntranslatedRedirectPageTest` of
  `academic_jobs` with its own fixture: a site with English and German with
  strict fallback, `/elsewhere` without a German translation named by the
  element, the site setting, a listener and `listPid`, the mode "never", and a
  hidden page, a deleted page and a folder for the English form, the mode
  "always" with the confirmation shown once, a notification mail that cannot
  be sent, plus a guard that the English form still reaches `/elsewhere`
  without a warning. Eight of the first nine tests red on
  TYPO3 v13 against the unchanged controller, with a `303` to
  `http://www.acme.com/` (`.agent/tmp/ace-932/red-13.log`), then on v14
  (`red-14.log`).
- [x] 1.2 The cases of the mode "always" and of the failing mail, added after
  review, red on v13 against the unchanged controller
  (`red-13-review.log`).
- [x] 1.3 The warning assertion shown to fail with the log call removed
  (`red-13-nolog.log`), and the confirmation of the conditional mode shown to
  fail when the first decision is kept (`red-13-nodecide.log`).

## 2. Implementation

- [x] 2.1 The job controller builds the address of the redirect page before it
  queues the confirmation, logs a warning for an empty one and decides again
  without a redirect page, verified by 1.1 green on v13 and v14.
- [x] 2.2 The deprecation helper of the redirect page test moved into
  `NewJobFormSubmissionTrait`, shared by both tests.

## 3. Documentation

- [x] 3.1 `Integrator/JobForm.rst` of `academic_jobs`: the redirect page
  option points to a new section on the redirect page in other languages, and
  the confirmation section names the unlinkable page.
- [x] 3.2 The 2.4 entry
  `Important-JobFormReturnsWhenRedirectPageCannotBeLinked.rst`, true on TYPO3
  v12 and v13 and with references that resolve on branch `2`, and a check in
  `Upgrade/From23To24.rst`.
- [x] 3.3 `docs/architecture/frontend-forms.md` explains the chain and the
  decision, `docs/testing/functional-tests.md` names the test.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_jobs` manual,
  green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-932`, and
  the archive of this change with the spec sync as the last commit.
