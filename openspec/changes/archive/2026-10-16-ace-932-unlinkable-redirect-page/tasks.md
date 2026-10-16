## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicJobsNewJobFormUntranslatedRedirectPageTest` of
  `academic_jobs` with its own fixture, on the notification mail test base of
  this branch: a site with English and German with strict fallback,
  `/elsewhere` without a German translation named by the element, the site
  setting, a listener and `listPid`, the modes "never" and "always", a
  notification mail that cannot be sent, a hidden and a deleted page for the
  English form, a folder in a method of the group `not-core-12`, its v12
  counterpart in the group `not-core-13`, and a guard that the English form
  still reaches `/elsewhere` without a warning. Red on TYPO3 v12 against the
  unchanged controller, 9 of 11 tests with a `303` to `http://www.acme.com/`
  (`.agent/tmp/ace-932-2/r3-red-12.log`), then on v13, 10 of 11
  (`r3-red-13.log`). The v12 link of a folder was checked first with the
  group removed (`folder-12.log`).
- [x] 1.2 The confirmation shown to fail when the first decision is kept, 8
  tests on v12 and 9 on v13 (`r3-mutation-nodecide-12.log`,
  `r3-mutation-nodecide-13.log`), and the warning assertion with the log call
  removed (`mutation-nolog-12.log`).

## 2. Implementation

- [x] 2.1 The job controller builds the address of the redirect page before it
  queues the confirmation, logs a warning for an empty one and decides again
  without a redirect page, verified by 1.1 green on v12 and v13.
- [x] 2.2 The deprecation helper of the redirect page test moved into
  `NewJobFormSubmissionTrait`, shared by both tests.

## 3. Documentation

- [x] 3.1 `Configuration/General/Index.rst` of `academic_jobs`: a section on
  the redirect page of the job form in other languages, and the confirmation
  section of `Templates/Override/Index.rst` names the unlinkable page.
- [x] 3.2 The 2.4 entry
  `Important-JobFormReturnsWhenRedirectPageCannotBeLinked.rst` and the check
  in `Upgrade/From23To24.rst`, both byte-identical with `main`, checked with
  `cmp`.
- [x] 3.3 `docs/testing/functional-tests.md` names the test and the folder
  split.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then for TYPO3 v12 and v13 each after its own
  `composerUpdate`: `cgl -n`, `phpstan`, `unit` and `functional -j auto` on
  SQLite, green.
- [x] 4.2 `lintMarkdown -n`, the rendering of the `academic_jobs` manual and
  `openspec validate --specs --strict`, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-932`, and
  the archive of this change with the spec sync as the last commit.
