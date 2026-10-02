## 1. Decisions before code

- [x] 1.1 Ask the maintainer whether a housing exchange belongs to the
  academic family and record the answer in `design.md`: not in scope now;
  only the move proceeds.
- [x] 1.2 Confirm ACE-508 is merged, as `design.md` decides, and verify that
  the jobs form partials `Forms/FieldWrapper.html` and `Forms/Textfield.html`
  no longer call the jobs-only validation ViewHelpers. Until both hold, stop
  here.

## 2. Safety net

- [x] 2.1 Run the existing jobs functional tests
  (`AcademicJobsNewJobFormPluginTest`, `AcademicJobsNewJobFormUploadTest`,
  `AcademicJobsListAndDetailPluginTest`) for v13 and v14 before touching
  code and record them green; they must pass unchanged after every task of
  group 3. All 267 jobs functional tests were run, green on v13 before the
  change, and v14 was green in the CI run of `main` it starts from.
- [x] 2.2 Add a functional test with a fixture partial path overriding
  `Job/Forms/Textfield.html` and assert the override renders, and one
  overriding `Job/Forms/FieldWrapper.html` that has to wrap every field. It passes
  today; break the delegation of task 3.2 on purpose once, watch it go red,
  restore.
- [x] 2.3 Add a test resolving `FGTCLB\AcademicJobs\SaveForm\FlashMessageCreationMode`
  after the move; break the alias map on purpose once, watch it go red,
  restore.

## 3. Move the submission pieces

- [x] 3.1 Move the enum to `academic_base` and register the old name in a
  class alias map of `academic_jobs`; verify task 2.3 and `phpstan` on v13
  and v14.
- [x] 3.2 Move the field partials to `academic_base` below `Academic/Form/`
  and let the jobs partials delegate, passing their own wrapper partial on.
  The base partial path is already in both jobs views at a lower key. Verify
  tasks 2.1 and 2.2.
- [x] 3.3 Extract the redirect and flash message decision, and the reading of
  its two settings, into a stateless service of `academic_base` and use it in
  the controller. Verify task 2.1 and unit tests for every mode of the enum.
- [x] 3.4 Ship the class alias map with the package: drop `/Migrations` from
  the `export-ignore` lines of `academic_jobs`, and add a test to
  `packages-dev/monorepo-shared` that fails for a declared map the archive of
  its package leaves out. Break it on purpose once, watch it go red, restore.

## 4. Documentation

- [x] 4.1 Add a section on the shared frontend submission pieces to
  `docs/architecture/`, linked from `docs/architecture/Index.md`, including
  the conditions for a second form extension from `proposal.md`.
- [x] 4.2 Add `Documentation/Changelog/3.0/Deprecation-FlashMessageCreationModeMovedToAcademicBase.rst`
  and `Important-NewJobFormFieldsRenderSharedPartials.rst` to `academic_jobs`
  and a `Feature-` entry for the new form pieces to `academic_base`, which
  documents its image partial the same way. Verify that both `3.0` indexes
  list them.

## 5. File the issue

- [x] 5.1 File the ACE issue in YouTrack, related to ACE-508, verify the key
  and rename the change to a slug that names the move: ACE-797,
  `ace-797-shared-form-pieces`.
- [x] 5.2 Commit in TYPO3 Core format `[TASK] ACE-<NNN>: <subject>`, subject
  at most 52 characters, body wrapped at 72.

## 6. Definition of done

- [x] 6.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [x] 6.2 The same for `-t 14` after its own `composerUpdate`.
- [x] 6.3 `functional -d postgres` green for v13 and v14, because the jobs
  form writes records.
- [x] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.5 `docs/` and the `Documentation/` changelog entries are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 6.6 Archive the change as the last commit of the pull request.
